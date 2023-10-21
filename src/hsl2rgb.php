<?php

function hsl2rgb($h, $s, $l) {
    $r;
    $g;
    $b;

    $c = ( 1 - abs(2 * $l - 1) ) * $s;
    $x = $c * ( 1 - abs(fmod(( $h / 60), 2) - 1) );
    $m = $l - ( $c / 2 );

    if ($h < 60) {
        $r = $c;
        $g = $x;
        $b = 0;
    } else if ($h < 120) {
        $r = $x;
        $g = $c;
        $b = 0;
    } else if ($h < 180) {
        $r = 0;
        $g = $c;
        $b = $x;
    } else if ($h < 240) {
        $r = 0;
        $g = $x;
        $b = $c;
    } else if ($h < 300) {
        $r = $x;
        $g = 0;
        $b = $c;
    } else {
        $r = $c;
        $g = 0;
        $b = $x;
    }

    $r = ( $r + $m ) * 255;
    $g = ( $g + $m ) * 255;
    $b = ( $b + $m ) * 255;

    return array(floor($r), floor($g), floor($b));
}
