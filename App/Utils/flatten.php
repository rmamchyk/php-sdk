<?php

namespace Evolv\Utils;

if (!function_exists('array_is_list')) {
    function array_is_list(array $array): bool
    {
        $i = 0;
        foreach ($array as $k => $v) {
            if ($k !== $i++) {
                return false;
            }
        }
        return true;
    }
}

function flatten_recursive(array $current, string $parentKey) {
    $items = [];

    foreach($current as $key => $value) {
        $newKey = $parentKey ? ($parentKey . '.' . $key) : $key;
        if (is_array($value) && !array_is_list($value)) {
            $items = array_merge($items, flatten_recursive($current[$key], $newKey));
        } else {
            $items[$newKey] = $value;
        }
    }

    return $items;
}

function flatten(array $array) {
    return flatten_recursive($array, '');
}
