<?php

namespace App\Utils;

function getValueForKey(string $key, array $array) {
    $value = null;
    $current = $array;
    $keys = explode('.', $key);

    for ($i = 0; $i < count($keys); $i++) {

        $k = $keys[$i];

        if (!isset($current[$k])) {
            break;
        }

        if ($i === (count($keys) - 1)) {
            $value = $current[$k];
            break;
        }

        $current = $current[$k];
    }

    return $value;
}

