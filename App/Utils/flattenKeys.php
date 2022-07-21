<?php

namespace App\Utils;

function flatten_keys_recursive(array $current, $parentKey, callable $filter)
{
    $items = [];

    $keys = array_filter(array_keys($current), $filter);

    foreach($keys as $key) {
        $value = $current[$key];
        $newKey = $parentKey ? ($parentKey . '.' . $key) : $key;
        $items[] = $newKey;
        if (is_array($value)) {
            $items = array_merge($items, flatten_keys_recursive($current[$key], $newKey, $filter));
        }
    }
    
    return $items;
}

function flattenKeys(array $array, callable $filter = null)
{
    return flatten_keys_recursive($array, '', $filter ?? function() { return true; });
}