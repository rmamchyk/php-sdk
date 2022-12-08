<?php

namespace Evolv\Utils;

function escapeSlashes($string) {
    return str_replace('/', '\/', str_replace('\/', '/', $string));
}

function regexFromString($string)
{
    $string = trim($string);

    if (strpos($string, '/') !== 0) {
        return '/' . escapeSlashes($string) . '/';
    }

    $lastIndex = strrpos($string, '/');
    $inner = substr($string, 1, $lastIndex  - 1);
    
    return '/' . escapeSlashes($inner) . substr($string, $lastIndex);
}
