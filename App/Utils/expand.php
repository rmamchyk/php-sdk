<?php

namespace App\Utils;

require_once __DIR__ . '/setKeyToValue.php';

function expand(array $array)
{
    $expanded = [];

    foreach($array as $key => $value) {
        setKeyToValue($key, $value, $expanded);
    }

    return $expanded;
}