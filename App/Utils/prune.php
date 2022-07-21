<?php

namespace App\Utils;

function prune(array $array, array $active)
{
    $pruned = [];

    foreach($active as $key) {
        $keys = explode('.', $key);
        $current = $array;
        for ($i = 0; $i < count($keys); $i++) { 
            $now = $current[$keys[$i]];
            if (isset($now)) {
                if ($i === (count($keys) - 1)) {
                    $pruned[$key] = $now;
                    break;
                }
                $current = $now;
            } else {
                break;
            }
        }
    }

    // TODO: handle predicated values
    // reattributePredicatedValues($pruned, $active);

    return $pruned;
}