<?php

namespace App\Utils;

function flatten_recursive(array $current, string $parentKey) {
    $items = [];

    foreach($current as $key => $value) {
        $newKey = $parentKey ? ($parentKey . '.' . $key) : $key;
        if (is_array($value)) {
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