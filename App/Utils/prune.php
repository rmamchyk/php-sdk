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

function reattribute(&$obj, array $active, array $collected) {
    if (!is_array($obj) || array_is_list($obj)) {
        return $obj;
    }

    if (!empty($obj['_predicated_values'])) {
        $predicatedKeyPrefix = implode('.', $collected);

        for ($i = 0; $i < count($obj['_predicated_values']); $i++) {
            if (in_array($predicatedKeyPrefix . '.' . $obj['_predicated_values'][$i]['_predicate_assignment_id'], $active)) {
                return $obj['_predicated_values'][$i]['_value'];
            }
        }

        return null;
    }

    $keys = array_keys($obj);

    for ($i = 0; $i < count($keys); $i++) {
        $key = $keys[$i];
        $newCollected = array_merge($collected, [$key]);

        $obj[$key] = reattribute($obj[$key], $active, $newCollected);
    }

    return $obj;
}

function reattributePredicatedValues(array &$pruned, array $active) {
    reattribute($pruned, $active, []);
}

function prune(array $array, array $active)
{
    $pruned = [];

    foreach($active as $key) {
        $keys = explode('.', $key);
        $current = $array;
        for ($i = 0; $i < count($keys); $i++) { 
            $now = isset($keys[$i]) && isset($current[$keys[$i]]) ? $current[$keys[$i]] : null;
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

    reattributePredicatedValues($pruned, $active);

    return $pruned;
}
