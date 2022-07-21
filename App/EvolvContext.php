<?php

declare(strict_types=1);

namespace App;

use function App\Utils\getValueForKey;
use function App\Utils\setKeyToValue;
use function App\Utils\removeValueForKey;
use function App\Utils\emit;
use function App\Utils\flatten;
require_once __DIR__ . '/Utils/getValueForKey.php';
require_once __DIR__ . '/Utils/setKeyToValue.php';
require_once __DIR__ . '/Utils/removeValueForKey.php';
require_once __DIR__ . '/Utils/waitForIt.php';
require_once __DIR__ . '/Utils/flatten.php';


const CONTEXT_CHANGED = 'context.changed';
const CONTEXT_INITIALIZED = 'context.initialized';
const CONTEXT_VALUE_REMOVED = 'context.value.removed';
const CONTEXT_VALUE_ADDED = 'context.value.added';
const CONTEXT_VALUE_CHANGED = 'context.value.changed';
const CONTEXT_DESTROYED = 'context.destroyed';

const DEFAULT_QUEUE_LIMIT = 50;

class EvolvContext
{
    public string $uid;
    public array $remoteContext = [];
    public array $localContext = [];
    private bool $initialized = false;

    private function ensureInitialized(): void
    {
        if (!$this->initialized) {
            throw new \Exception('Evolv: The context is not initialized');
        }
    }

    public function resolve()
    {
        $this->ensureInitialized();

        return array_merge_recursive($this->remoteContext, $this->localContext);
    }

    public function initialize($uid, $remoteContext = [], $localContext = [])
    {
        if ($this->initialized) {
            throw new \Exception('Evolv: The context is already initialized');
        }

        $this->uid = $uid;

        // TODO: clone the remoteContext passed from args
        $this->remoteContext = $remoteContext;

        // TODO: clone the localContext passed from args
        $this->localContext = $localContext;

        $this->initialized = true;

        emit(CONTEXT_INITIALIZED, $this->resolve());
    }

    public function __destruct()
    {
        emit(CONTEXT_DESTROYED);
    }

    /**
     * Sets a value in the current context.
     *
     * Note: This will cause the effective genome to be recomputed.
     * 
     * @param string $key The key to associate the value to.
     * @param mixed $value The value to associate with the key.
     * @param bool $local If true, the value will only be added to the localContext.
     * @return bool True if context value has been changes, otherwise false.
     */ 
    public function set(string $key, $value, bool $local = false): void
    {
        $this->ensureInitialized();

        $before = null;
        if ($local) {
            $before = getValueForKey($key, $this->localContext);
        } else {
            $before = getValueForKey($key, $this->remoteContext);
        }

        if ($local) {
            setKeyToValue($key, $value, $this->localContext);
        } else {
            setKeyToValue($key, $value, $this->remoteContext);
        }

        if (is_null($before)) {
            emit(CONTEXT_VALUE_ADDED, $key, $value, $local);
        } else {
            emit(CONTEXT_VALUE_CHANGED, $key, $value, $before, $local);
        }
        emit(CONTEXT_CHANGED, $this->resolve());
    }

    /**
     * Retrieve a value from the context.
     *
     * @param {String} key The kay associated with the value to retrieve.
     * @returns {*} The value associated with the specified key.
     */
    public function get(string $key)
    {
        $this->ensureInitialized();

        $value = getValueForKey($key, $this->remoteContext);
        if (!$value) {
            $value = getValueForKey($key, $this->localContext);
        }

        return $value;
    }

    /**
     * Remove a specified key from the context.
     *
     * Note: This will cause the effective genome to be recomputed.
     *
     * @param key {String} The key to remove from the context.
     */
    public function remove(string $key)
    {
        $this->ensureInitialized();
        $local = removeValueForKey($key, $this->localContext);
        $remote = removeValueForKey($key, $this->remoteContext);
        $removed = $local || $remote;
    
        if ($removed) {
            $updated = $this->resolve();
            emit(CONTEXT_VALUE_REMOVED, $key, !$remote, $updated);
            emit(CONTEXT_CHANGED, $updated);
        }
    
        return $removed;
    }

    /**
     * Merge the specified object into the current context.
     *
     * Note: This will cause the effective genome to be recomputed.
     *
     * @param update {Object} The values to update the context with.
     * @param local {Boolean} If true, the values will only be added to the localContext.
     */
    public function update(array $update, $local = false) {
        $this->ensureInitialized();
        $context = null;
    
        if ($local) {
            $context = &$this->localContext;
        } else {
            $context = &$this->remoteContext;
        }

        $flattened = flatten($update);
        $flattenedBefore = flatten($context);

        if ($local) {
            $this->localContext = array_merge_recursive($this->localContext, $update);
        } else {
            $this->remoteContext = array_merge_recursive($this->remoteContext, $update);
        }

        $updated = $this->resolve();
        foreach ($flattened as $key => $value) {
            if (!array_key_exists($key, $flattenedBefore)) {
                emit(CONTEXT_VALUE_ADDED, $key, $value, $local, $updated);
            } else if ($flattenedBefore[$key] !== $value) {
                emit(CONTEXT_VALUE_CHANGED, $key, $value, $flattenedBefore[$key], $local, $updated);
            }
        }
        emit(CONTEXT_CHANGED, $updated);
    }

    /**
     * Checks if the specified key is currently defined in the context.
     *
     * @param key The key to check.
     * @returns {boolean} True if the key has an associated value in the context.
     */
    public function contains(string $key)
    {
        $this->ensureInitialized();

        return array_key_exists($key, $this->remoteContext) || array_key_exists($key, $this->localContext);
    }

    /**
     * Adds value to specified array in context. If array doesnt exist its created and added to.
     *
     * @param key The array to add to.
     * @param value Value to add to the array.
     * @param local {Boolean} If true, the value will only be added to the localContext.
     * @param limit {Number} Max length of array to maintain.
     * @returns {boolean} True if value was successfully added.
     */
    public function pushToArray(string $key, $value, $local = false, $limit = null)
    {
        $limit = $limit ?? DEFAULT_QUEUE_LIMIT;

        $this->ensureInitialized();

        if ($local) {
            $context = &$this->localContext;
        } else {
            $context = &$this->remoteContext;
        }

        $originalArray = getValueForKey($key, $context) ?? [];

        $combined = array_merge($originalArray, [$value]);

        $newArray = array_slice($combined, count($combined) - $limit);

        return $this->set($key, $newArray, $local);
    }
}