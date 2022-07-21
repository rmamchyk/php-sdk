<?php

namespace App\Utils;

function set_key_recursive(string $k, array $keys, $value, array &$array)
{
    if (!count($keys)) {
        $array[$k] = $value;
        return;
    }

    if (!array_key_exists($k, $array)) {
        $array[$k] = [];
    }

    $nextKey = array_shift($keys);

    set_key_recursive($nextKey, $keys, $value, $array[$k]);
}

function setKeyToValue(string $key, $value, array &$array)
{
    $keys = explode('.', $key);

    $k = array_shift($keys);

    set_key_recursive($k, $keys, $value, $array);
}
