<?php

namespace App\Helper;

class NumberHelper
{
    /**
     * Round a number to 2 decimal places
     *
     * @param float $value
     * @return float
     */
    public static function round2(float $value): float
    {
        return round($value, 2);
    }
}