<?php

declare(strict_types=1);

namespace App;

use App\EvolvContext;
use App\Predicate;
use App\HttpClient;

use function App\Utils\waitFor;
use function App\Utils\emit;
use function App\Utils\flattenKeys;
use function App\Utils\filter;
use function App\Utils\getValueForKey;
use function App\Utils\prune;

require_once __DIR__ . '/EvolvContext.php';
require_once __DIR__ . '/Predicate.php';
require_once __DIR__ . '/HttpClient.php';
require_once __DIR__ . '/Utils/flattenKeys.php';
require_once __DIR__ . '/Utils/filter.php';
require_once __DIR__ . '/Utils/prune.php';
require_once __DIR__ . '/Utils/getValueForKey.php';
require_once __DIR__ . '/Utils/waitForIt.php';

const CONFIG_SOURCE = 'config';
const GENOME_SOURCE = 'genome';

const GENOME_REQUEST_SENT = 'genome.request.sent';
const CONFIG_REQUEST_SENT = 'config.request.sent';
const GENOME_REQUEST_RECEIVED = 'genome.request.received';
const CONFIG_REQUEST_RECEIVED = 'config.request.received';
const REQUEST_FAILED = 'request.failed';
const GENOME_UPDATED = 'genome.updated';
const CONFIG_UPDATED = 'config.updated';
const EFFECTIVE_GENOME_UPDATED = 'effective.genome.updated';
const STORE_DESTROYED = 'store.destroyed';


function startsWith( $haystack, $needle ) {
    $length = strlen( $needle );
    return substr( $haystack, 0, $length ) === $needle;
}

function endsWith( $haystack, $needle ) {
    $length = strlen( $needle );
    if( !$length ) {
        return true;
    }
    return substr( $haystack, -$length ) === $needle;
}

function array_some(array $array, callable $filter) {
    foreach ($array as $value) {
        if ($filter($value)) {
            return true;
        }
    }
    return false;
}

class EvolvStore
{
    private HttpClient $httpClient;
    private bool $initialized = false;
    private string $environment;
    private string $endpoint;
    private EvolvContext $context;
    private Predicate $predicate;

    public $config = null;
    public $allocations = null;
    public $effectiveGenome = null;
    private $configFailed = false;
    private $genomeFailed = false;
    private $genomes = [];
    private $reevaluatingContext = false;

    public $genomeKeyStates = [
        'needed' => [],
        'requested' => [],
        'experiments' => []
    ];
    public $configKeyStates = [
        'needed' => [],
        'requested' => [],
        'experiments' => []
    ];
    public array $activeEids = [];
    public array $activeKeys = [];
    public array $activeVariants = [];

    private array $subscriptions = [];

    public function __construct(string $environment, string $endpoint)
    {
        $this->environment = $environment;
        $this->endpoint = $endpoint;

        $this->httpClient = new HttpClient();
        $this->predicate = new Predicate();
    }

    private function evaluateBranch(array $context, array $config, string $prefix, array &$disabled, array &$entry)
    {
        if (!isset($config) || !is_array($config)) {
            return;
        }

        if (isset($config['_predicate'])) {
            $result = $this->predicate->evaluate($context, $config['_predicate']);
            if ($result['rejected']) {
                $disabled[] = $prefix;
                return;
            }
        }

        if (isset($config['_is_entry_point']) && $config['_is_entry_point'] === true) {
            $entry[] = $prefix;
        }

        $keys = array_filter(array_keys($config), function($key) { return !startsWith($key, '_'); });
    
        foreach($keys as $key) {
            $this->evaluateBranch($context, $config[$key], $prefix ? ($prefix . '.' . $key) : $key, $disabled, $entry);
        }
    }

    private function evaluatePredicates(array $context, array $config) {
        $result = [];

        if (!isset($config['_experiments']) || !count($config['_experiments'])) {
            return $result;
        }

        foreach($config['_experiments'] as $exp) {
            $evaluableConfig = $exp;
            unset($evaluableConfig['id']);
            $expResult = [
                'disabled' => [],
                'entry' => []
            ];

            $this->evaluateBranch($context, $evaluableConfig, '', $expResult['disabled'], $expResult['entry']);
            $result[$exp['id']] = $expResult;
        }

        return $result;
    }

    private function getActiveAndEntryExperimentKeyStates(array $results, array $keyStatesLoaded)
    {
        $expKeyStates = [
            'active' => [],
            'entry' => []
        ];

        foreach($keyStatesLoaded as $key) {
            $active = !array_some($results['disabled'], function($disabledKey) use ($key) {
                return startsWith($key, $disabledKey);
            });

            if ($active) {
                $expKeyStates['active'][] = $key;
                $entry = array_some($results['entry'], function($entryKey) use ($key) {
                    return startsWith($key, $entryKey);
                });

                if ($entry) {
                    $expKeyStates['entry'][] = $key;
                }
            }
        }

        return $expKeyStates;
    }

    private function evaluateAllocationPredicates()
    {
        // TODO: not implemented yet
    }

    private function setActiveAndEntryKeyStates()
    {
        $results = $this->evaluatePredicates($this->context->resolve(), $this->config);

        foreach($results as $eid => $expResults) {
            $expConfigKeyStates = &$this->configKeyStates['experiments'][$eid];
            if (!isset($expConfigKeyStates)) {
                return;
            }

            $expConfigLoaded = &$expConfigKeyStates['loaded'];

            $loadedKeys = [];

            if (isset($expConfigLoaded)) {
                foreach($expConfigLoaded as $key) {
                    $loadedKeys[] = $key;
                }
            }

            $newExpKeyStates = $this->getActiveAndEntryExperimentKeyStates($expResults, $loadedKeys);

            $activeKeyStates = [];
            foreach($newExpKeyStates['active'] as $key) {
                $activeKeyStates[] = $key;
            }

            $allocation = array_filter($this->allocations, function($a) use ($eid) { return $a['eid'] === $eid; })[0];
            if (isset($allocation)) {
                $this->evaluateAllocationPredicates();
            }

            $entryKeyStates = [];

            foreach($newExpKeyStates['entry'] as $key) {
                $entryKeyStates[] = $key;
            }

            $expConfigKeyStates['active'] = $activeKeyStates;
            $expConfigKeyStates['entry'] = $entryKeyStates;
        }
    }

    private function generateEffectiveGenome(array $expsKeyStates, $genomes)
    {
        $effectiveGenome = [];
        $activeEids = [];

        foreach($expsKeyStates as $eid => $expKeyStates) {
            $active = $expKeyStates['active'];
            if (array_key_exists($eid, $genomes) && $active) {
                $activeGenome = filter($genomes[$eid], $active);

                if (count(array_keys($activeGenome))) {
                    $activeEids[] = $eid;
                    $effectiveGenome = array_merge_recursive($effectiveGenome, $activeGenome);
                }
            }
        }

        return [
            'effectiveGenome' => $effectiveGenome,
            'activeEids' => $activeEids,
        ];
    }

    public function reevaluateContext()
    {
        if (!isset($this->config)) {
            return;
        }

        if ($this->reevaluatingContext) {
            return;
        }

        $this->reevaluatingContext = true;

        $this->setActiveAndEntryKeyStates();
        $result = $this->generateEffectiveGenome($this->configKeyStates['experiments'], $this->genomes);

        $this->effectiveGenome = $result['effectiveGenome'];
        $this->activeEids = $result['activeEids'];

        $this->activeKeys = [];
        $this->activeVariants = [];

        foreach($this->configKeyStates['experiments'] as $expKeyStates) {
            $active = $expKeyStates['active'];
            if ($active) {
                foreach($active as $key) {
                    $this->activeKeys[] = $key;
                }
                $pruned = prune($this->effectiveGenome, $active);
                foreach($pruned as $key => $value) {
                    $this->activeVariants[] = $key . ':' . 'hashCode';
                }
            }
        }

        $this->context->set('keys.active', $this->activeKeys);
        $this->context->set('variants.active', $this->activeVariants);

        emit(EFFECTIVE_GENOME_UPDATED, $this->effectiveGenome);

        foreach($this->subscriptions as $listener) {
            $listener($this->effectiveGenome, $this->config);
        }

        $this->reevaluatingContext = false;
    }

    public function initialize(EvolvContext $context)
    {
        if ($this->initialized) {
            throw new \Exception('Evolv: The store has already been initialized.');
        }

        $this->context = $context;
        $this->initialized = true;

        $this->pull();

        waitFor(CONTEXT_CHANGED, function() {
            $this->reevaluateContext();
        });
    }

    private function setConfigLoadedKeys($exp)
    {
        $clean = $exp;
        unset($clean['id']);

        $expLoaded = [];
        $expMap = [
            'loaded' => &$expLoaded
        ];

        $this->configKeyStates['experiments'][$exp['id']] = &$expMap;

        $keys = flattenKeys($clean, function($key) {
            return strpos($key, '_') !== 0 || $key === '_values' || $key === '_initializers';
        });

        $filteredKeys = array_filter($keys, function($key) {
            return endsWith($key, '_values') || endsWith($key, '_initializers');
        });

        foreach($filteredKeys as $key) {
            $cleanKey = str_replace(['._values', '._initializers'], '', $key);
            if (!in_array($cleanKey, $expLoaded)) {
                $expLoaded[] = $cleanKey;
            }
        }
    }

    private function updateConfig(array $value)
    {
        $this->config = $value;
        $this->configFailed = false;

        if (isset($config['_client'])) {
            $clientContext = $config['_client'];
        }

        foreach ($value['_experiments'] as $exp) {
            $this->setConfigLoadedKeys($exp);
        }
    }

    private function updateGenome(array $value)
    {
        $allocs = [];
        $exclusions = [];

        $this->allocations = $value;

        $this->genomeFailed = false;

        foreach($value as $alloc) {
            $clean = $alloc;
            unset($clean['genome']);
            unset($clean['audience_query']);

            $allocs[] = $clean;

            if ($clean['excluded']) {
                $exclusions[] = $clean['eid'];
                return;
            }

            $this->genomes[$clean['eid']] = $alloc['genome'];
            
            $expLoaded = [];
            $expMap = [
                'loaded' => &$expLoaded
            ];

            $this->genomeKeyStates['experiments'][$clean['eid']] = &$expMap;

            $keys = flattenKeys($alloc['genome'], function($key) {
                return !startsWith($key, '_');
            });

            foreach($keys as $key) {
                $expLoaded[] = $key;
            }

            $this->context->set('experiments.allocations', $allocs);
            $this->context->set('experiments.exclusions', $exclusions);
        }
    }

    private function update($config, $allocation)
    {
        $this->updateConfig($config);
        $this->updateGenome($allocation);

        $this->reevaluateContext();
    }

    private function pull()
    {
        $allocationUrl = $this->endpoint . 'v1/' . $this->environment . '/' . $this->context->uid . '/allocations';
        $configUrl = $this->endpoint . 'v1/' . $this->environment . '/' . $this->context->uid . '/configuration.json';

        $arr_location = $this->httpClient->request($allocationUrl);
        $arr_config = $this->httpClient->request($configUrl);

        $arr_config = json_decode($arr_config, true);
        $arr_location = json_decode($arr_location, true);

        $this->genomeKeyStates = [
            'needed' => [],
            'requested' => [],
            'experiments' => [],
        ];

        $this->configKeyStates = [
            'needed' => [],
            'requested' => [],
            'experiments' => [],
        ];

        $this->update($arr_config, $arr_location);
    }

    public function getActiveKeys(string $prefix = null)
    {
        return array_filter($this->activeKeys, function($key) use ($prefix) {
            return !$prefix || startsWith($key, $prefix);
        });
    }

    public function getValue(string $key, $effectiveGenome) {
        return getValueForKey($key, $effectiveGenome);
    } 

    public function activeEntryPoints()
    {
        $eids = [];

        foreach($this->configKeyStates['experiments'] as $eid => $expKeyStates) {
            $entry = $expKeyStates['entry'];
            if ($entry && count($entry)) {
                $eids[] = $eid;
            }
        }

        return $eids;
    }

    public function createSubscribable(string $functionName, $key, callable $listener = null)
    {
        if (isset($listener)) {
            $this->subscriptions[] = function($effectiveGenome, $config) use ($listener, $functionName, $key) {
                $listener(call_user_func_array([$this, $functionName], [$key, $effectiveGenome, $config]));
            };
        } else {
            return call_user_func_array([$this, $functionName], [$key, $this->effectiveGenome, $this->config]);
        }
    }
}
