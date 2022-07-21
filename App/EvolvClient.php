<?php

declare(strict_types=1);

namespace App;

use App\EvolvContext;
use App\EvolvStore;
use App\Beacon;

use function App\Utils\waitFor;
use function App\Utils\emit;

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/EvolvStore.php';
require_once __DIR__ . '/Beacon.php';
require_once __DIR__ . '/Utils/waitForIt.php';

class EvolvClient
{
    const INITIALIZED = 'initialized';
    const CONFIRMED = 'confirmed';
    const CONTAMINATED = 'contaminated';
    const EVENT_EMITTED = 'event.emitted';

    public bool $initialized = false;
    public EvolvContext $context;
    private $store;
    private bool $autoconfirm;
    private Beacon $contextBeacon;
    private Beacon $eventBeacon;

    public function __construct(string $environment, string $endpoint = 'https://participants.evolv.ai/', bool $autoconfirm = true)
    {
        $this->context = new EvolvContext();
        $this->store = new EvolvStore($environment, $endpoint);
        
        $this->contextBeacon = new Beacon($endpoint . 'v1/' . $environment . '/data', $this->context);
        $this->eventBeacon = new Beacon($endpoint . 'v1/' . $environment . '/events', $this->context);
        
        $this->autoconfirm = $autoconfirm;
    }

    /**
     * Initializes the client with required context information.
     *
     * @param {String} uid A globally unique identifier for the current participant.
     * @param {String} sid A globally unique session identifier for the current participant.
     * @param {Object} remoteContext A map of data used for evaluating context predicates and analytics.
     * @param {Object} localContext A map of data used only for evaluating context predicates.
     */

    public function initialize(string $uid, array $remoteContext = [], array $localContext = [])
    {
        if ($this->initialized) {
            throw new \Exception('Evolv: Client is already initialized');
        }

        if (!$uid) {
            throw new \Exception('Evolv: "uid" must be specified');
        }

        $this->context->initialize($uid, $remoteContext, $localContext);
        $this->store->initialize($this->context);

        if ($this->autoconfirm) {
            $this->confirm();
        }

        $this->initialized = true;

        waitFor(CONTEXT_INITIALIZED, function($type, $ctx) {
            $this->contextBeacon->emit($type, $this->context->remoteContext);
        });

        waitFor(CONTEXT_VALUE_ADDED, function($type, $key, $value, $local) {
            if ($local) {
                return;
            }
            $this->contextBeacon->emit($type, ['key' => $key, 'value' => $value]);
        });
        waitFor(CONTEXT_VALUE_CHANGED, function($type, $key, $value, $local) {
            if ($local) {
                return;
            }
            $this->contextBeacon->emit($type, ['key' => $key, 'value' => $value]);
        });

        emit(EvolvClient::INITIALIZED);
    }

    /**
     * Add listeners to lifecycle events that take place in to client.
     *
     * Currently supported events:
     * * "initialized" - Called when the client is fully initialized and ready for use with (topic, options)
     * * "context.initialized" - Called when the context is fully initialized and ready for use with (topic, updated_context)
     * * "context.changed" - Called whenever a change is made to the context values with (topic, updated_context)
     * * "context.value.removed" - Called when a value is removed from context with (topic, key, updated_context)
     * * "context.value.added" - Called when a new value is added to the context with (topic, key, value, local, updated_context)
     * * "context.value.changed" - Called when a value is changed in the context (topic, key, value, before, local, updated_context)
     * * "context.destroyed" - Called when the context is destroyed with (topic, context)
     * * "genome.request.sent" - Called when a request for a genome is sent with (topic, requested_keys)
     * * "config.request.sent" - Called when a request for a config is sent with (topic, requested_keys)
     * * "genome.request.received" - Called when the result of a request for a genome is received (topic, requested_keys)
     * * "config.request.received" - Called when the result of a request for a config is received (topic, requested_keys)
     * * "request.failed" - Called when a request fails (topic, source, requested_keys, error)
     * * "genome.updated" - Called when the stored genome is updated (topic, allocation_response)
     * * "config.updated" - Called when the stored config is updated (topic, config_response)
     * * "effective.genome.updated" - Called when the effective genome is updated (topic, effectiveGenome)
     * * "store.destroyed" - Called when the store is destroyed (topic, store)
     * * "confirmed" - Called when the consumer is confirmed (topic)
     * * "contaminated" - Called when the consumer is contaminated (topic)
     * * "event.emitted" - Called when an event is emitted through the beacon (topic, type, score)
     *
     * @param string topic The event topic on which the listener should be invoked.
     * @param callable listener The listener to be invoked for the specified topic.
     * @method
     * @see {@link EvolvClient#once} for listeners that should only be invoked once.
     */
    public function on(string $topic, callable $listener)
    {
        waitFor($topic, $listener);
    }

    /**
     * Send an event to the events endpoint.
     *
     * @param {String} type The type associated with the event.
     * @param metadata {Object} Any metadata to attach to the event.
     * @param flush {Boolean} If true, the event will be sent immediately.
     */
    public function emit(string $type, $metadata, bool $flush = false)
    {
        $this->context->pushToArray('events', ['type' => $type, 'timestamp' => time()]);
        $this->eventBeacon->emit($type, [
            'uid' => $this->context->uid,
            'metadata' => $metadata
        ], $flush);
        emit(EvolvClient::EVENT_EMITTED, $type, $metadata);
    }

    /**
     * Check all active keys that start with the specified prefix.
     *
     * @param {String} prefix The prefix of the keys to check.
     * @returns {SubscribablePromise.<Object|Error>} A SubscribablePromise that resolves to object
     * describing the state of active keys.
     * @method
     */
    public function getActiveKeys(string $prefix = '', callable $listener = null)
    {
        return $this->store->createSubscribable('getActiveKeys', $prefix, $listener);
    }

    /**
     * Get the value of a specified key.
     *
     * @param {String} key The key of the value to retrieve.
     * @returns @mixed A value of the specified key.
     * @method
     */
    public function get(string $key = '', callable $listener = null)
    {
        return $this->store->createSubscribable('getValue', $key, $listener);
    }

    /**
     * Confirm that the consumer has successfully received and applied values, making them eligible for inclusion in
     * optimization statistics.
     */
    public function confirm()
    {
        waitFor(EFFECTIVE_GENOME_UPDATED, function() {
            $allocations = $this->context->get('experiments.allocations');
            if (!isset($allocations) || !count($allocations)) {
                return;
            }

            $entryPointEids = $this->store->activeEntryPoints();
            if (!count($entryPointEids)) {
                return;
            }

            $confirmations = $this->context->get('experiments.confirmations') ?? [];
            $confirmedCids = array_map(function($item) { return $item['cid']; }, $confirmations);

            $contaminations = $this->context->get('experiments.contaminations') ?? [];
            $contaminatedCids = array_map(function($item) { return $item['cid']; }, $contaminations);

            $confirmableAllocations = array_filter($allocations, function($alloc) use ($confirmedCids, $contaminatedCids, $entryPointEids) {
                return !in_array($alloc['cid'], $confirmedCids) &&
                    !in_array($alloc['cid'], $contaminatedCids) &&
                    in_array($alloc['eid'], $this->store->activeEids) &&
                    in_array($alloc['eid'], $entryPointEids);
            });
            if (!count($confirmableAllocations)) {
                return;
            }

            $timestamp = time();

            $contextConfirmations = array_map(function($alloc) use ($timestamp) {
                return [
                    'cid' => $alloc['cid'],
                    'timestamp' => $timestamp
                ];
            }, $confirmableAllocations);

            $newConfirmations = array_merge($contextConfirmations, $confirmations);
            $this->context->update(['experiments' => ['confirmations' => $newConfirmations]]);

            foreach ($confirmableAllocations as $alloc) {
                $this->eventBeacon->emit('confirmation', array_merge([
                    'uid' => $alloc['uid'],
                    'eid' => $alloc['eid'],
                    'cid' => $alloc['cid']
                ], $this->context->remoteContext));
            };

            $this->eventBeacon->flush();
            emit(EvolvClient::CONFIRMED);
        });
    }

    /**
     * Marks a consumer as unsuccessfully retrieving and / or applying requested values, making them ineligible for
     * inclusion in optimization statistics.
     *
     * @param details {Object} Optional. Information on the reason for contamination. If provided, the object should
     * contain a reason. Optionally, a 'details' value should be included for extra debugging info
     * @param {boolean} allExperiments If true, the user will be excluded from all optimizations, including optimization
     * not applicable to this page
     */
    public function contaminate($details, bool $allExperiments = false)
    {
        $allocations = $this->context->get('experiments.allocations');
        if (!isset($allocations) || !count($allocations)) {
          return;
        }

        if (!isset($details['reason'])) {
            throw new \Exception('Evolv: contamination details must include a reason');
        }

        $contaminations = $this->context->get('experiments.contaminations') ?? [];
        $contaminatedCids = array_map(function($item) { return $item['cid']; }, $contaminations);

        $contaminatableAllocations = array_filter($allocations, function($alloc) use ($contaminatedCids, $allExperiments) {
            return !in_array($alloc['cid'], $contaminatedCids) &&
                ($allExperiments || in_array($alloc['eid'], $this->store->activeEids));
        });
        if (!count($contaminatableAllocations)) {
            return;
        }

        $timestamp = time();

        $contextContaminations = array_map(function($alloc) use ($timestamp) {
            return [
                'cid' => $alloc['cid'],
                'timestamp' => $timestamp
            ];
        }, $contaminatableAllocations);

        $newContaminations = array_merge($contextContaminations, $contaminations);
        $this->context->update(['experiments' => ['contaminations' => $newContaminations]]);

        foreach ($contaminatableAllocations as $alloc) {
            $this->eventBeacon->emit('contamination', array_merge([
                'uid' => $alloc['uid'],
                'eid' => $alloc['eid'],
                'cid' => $alloc['cid'],
                'contaminationReason' => $details
            ], $this->context->remoteContext));
        };
    
        $this->eventBeacon->flush();
        emit(EvolvClient::CONTAMINATED);
    }
}







