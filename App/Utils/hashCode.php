<?php

namespace Evolv\Utils;

function int32Bit($value)
{
    return $value & 0xffffffff;
}

function shiftLeft32( $a, $b ) {
    $a = int32Bit($a);

    return ($c = $a << $b) && $c > 0x7FFFFFFF ? ($c % 0x80000000) - 0x80000000 : $c;
}

function hashCode(string $str) {
    $ret = 0;

    for($i = 0; $i < strlen($str); $i++) {
        $ret = shiftLeft32((31 * $ret + mb_ord($str[$i], 'UTF-8')), 0);
    }

    return $ret;
}
