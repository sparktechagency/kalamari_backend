<?php

if (!function_exists('kmCount')) {
    function kmCount($number)
    {
        if ($number >= 1000000) {
            $formatted = $number / 1000000;
            return rtrim(rtrim(number_format($formatted, 1), '0'), '.') . 'M';
        } elseif ($number >= 1000) {
            $formatted = $number / 1000;
            return rtrim(rtrim(number_format($formatted, 1), '0'), '.') . 'K';
        }
        return $number;
    }
}

if (!function_exists('formatCount')) {
    function formatCount($number) {
        // ...
    }
}
