<?php

declare(strict_types=1);

namespace Evolv;

use function Evolv\Utils\getValueForKey;
use function Evolv\Utils\regexFromString;

require_once __DIR__ . '/../App/Utils/getValueForKey.php';
require_once __DIR__ . '/../App/Utils/regexFromString.php';


function regex64Match($value, $b64pattern): bool
{
    $pattern = base64_decode($b64pattern);

    return regexMatch($value, $pattern);
}

function regexMatch($value, $pattern): bool
{
    try {
        return isset($value) && preg_match(regexFromString($pattern), $value, $matches);
    } catch (\Throwable $th) {
        return false;
    }
}

class Predicate
{
    private array $filters;

    public function __construct()
    {
        $this->filters = [
            'contains' => function ($a, $b) { return strpos($a, $b) !== false; },
            'defined' => function ($a, $b) { return isset($a); },
            'equal' => function ($a, $b) { return $a === $b; },
            'exists' => function ($a, $b) { return isset($a); },
            'greater_than' => function ($a, $b) { return ($a > $b) ? true : false; },
            'greater_than_or_equal_to' => function ($a, $b) { ($a >= $b) ? true : false; },
            'is_true' => function ($a, $b) { return $a === true; },
            'is_false' => function ($a, $b) { return $a === false; },
            'not_exists' => function ($a, $b) { return !isset($a); },
            'not_contains' => function ($a, $b) { return strpos($a, $b) === false; },
            'not_defined' => function ($a, $b) { return !isset($a); },
            'not_equal' => function ($a, $b) { return ($a !== $b) ? true : false; },
            'not_regex_match' => function ($value, $pattern) { return !regexMatch($value, $pattern); },
            'not_regex64_match' => function ($value, $pattern) { return !regex64Match($value, $pattern); },
            'not_starts_with' => function ($a, $b) { return strpos($a, $b) !== 0; },
            'kv_contains' => function ($obj, $params) { return array_key_exists($params[0], $obj) && strpos($obj[$params[0]], $params[1]) !== false; },
            'kv_equal' => function ($obj, $params) { return array_key_exists($params[0], $obj) && $obj[$params[0]] === $params[1]; },
            'kv_not_contains' => function ($obj, $params) { return !(array_key_exists($params[0], $obj) && strpos($obj[$params[0]], $params[1]) !== false); },
            'kv_not_equal' => function ($obj, $params) { return !(array_key_exists($params[0], $obj) && $obj[$params[0]] === $params[1]); },
            'less_than' => function ($a, $b) { return $a < $b; },
            'less_than_or_equal_to' => function ($a, $b) { return $a <= $b; },
            'loose_equal' => function ($a, $b) { return $a == $b; },
            'loose_not_equal' => function ($a, $b) { return $a != $b; },
            'regex_match' => function ($value, $pattern) { return regexMatch($value, $pattern); },
            'regex64_match' => function ($value, $pattern) { return regex64Match($value, $pattern); },
            'starts_with' => function ($a, $b) { return strpos($a, $b) === 0; }
        ];
    }

    private function evaluateFilter($context, $rule): bool
    {
        $value = getValueForKey($rule['field'], $context);

        if (strpos($rule['operator'], 'kv_') === 0 && !$value) {
            return false;
        }

        return $this->filters[$rule['operator']]($value, $rule['value']);
    }

    private function evaluateRule($context, $predicate, $rule, array &$passedRules, array &$failedRules): bool
    {
        $result = false;

        if (isset($rule['combinator'])) {
            // No need to add groups to pass/failed rule sets here. Their children results will be merged up
            // via recursion.
            return $this->evaluatePredicate($context, $rule, $passedRules, $failedRules);
        } else {
            $result = $this->evaluateFilter($context, $rule);
        }

        $ruleResult = [
            'id' => $predicate['id'] ?? null,
            'field' => $rule['field']
        ];

        if ($result) {
            $passedRules[] = $ruleResult;
        } else {
            $failedRules[] = $ruleResult;
        }

        return $result;
    }

    private function evaluatePredicate($context, $predicate, array &$passedRules, array &$failedRules): bool
    {
        if (!isset($predicate['rules'])) {
            return true;
        }

        $rules = $predicate['rules'];

        for ($i = 0; $i < count($rules); $i++) { 
            $passed = $this->evaluateRule($context, $predicate, $rules[$i], $passedRules, $failedRules);
            if ($passed && $predicate['combinator'] === 'or') {
                return true;
            }
            if (!$passed && $predicate['combinator'] === 'and') {
                return false;
            }
        }

        // If we've reached this point on an 'or' all rules failed.
        return $predicate['combinator'] === 'and';
    }

    /**
     * @typedef EvaluationResult
     * @property array passed
     * @property array failed
     * @property array rejected
     * @property array touched
     */

    /**
     * Evaluates a query against a user object and saves passing/failing rule ids to provided sets.
     * @param array context A context object containing describing the context the predicate should be evaluated against.
     * @param array predicate Nested predicate object that rules structured into groups as a deeply nested tree.
     *                  note: There is no set limit to the depth of this tree, hence we must work with it
     *                  using recursion.
     * @return {EvaluationResult}
     */
    public function evaluate($context, $predicate)
    {
        $result = [
            'passed' => [],
            'failed' => [],
            'touched' => [],
            'rejected' => []
        ];

        $result['rejected'] = !$this->evaluatePredicate($context, $predicate, $result['passed'], $result['failed']);

        foreach ($result['passed'] as $item) {
            $result['touched'][] = $item['field'];
        }

        foreach ($result['failed'] as $item) {
            $result['touched'][] = $item['field'];
        }

        return $result;
    }
}
