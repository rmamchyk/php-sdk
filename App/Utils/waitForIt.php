<?php

namespace App\Utils;

$scopedHandlers = [];

function ensureScope($it)
{
    global $scopedHandlers;

    if (array_key_exists($it, $scopedHandlers)) {
      return;
    }
  
    $scopedHandlers[$it] = [];
}

function waitFor(string $it, callable $handler)
{
    global $scopedHandlers;

    ensureScope($it);
  
    $scopedHandlers[$it][] = $handler;
}

function emit($it, ...$rest)
{
    global $scopedHandlers;
    ensureScope($it);

    $handlers = $scopedHandlers[$it];

    $payload = $rest;
    
    array_unshift($payload, $it);

    if (!isset($handlers) || !count($handlers)) {
      return;
    }

    foreach ($handlers as $handler) {
        $handler(...$payload);
    }
}