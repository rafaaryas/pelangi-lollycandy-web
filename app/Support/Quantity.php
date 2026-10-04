<?php

namespace App\Support;

final class Quantity
{
    public static function format(float|int|string|null $value): string
    {
        $number = (float) $value;
        $decimals = abs($number - round($number)) < 0.0005 ? 0 : 3;

        return $decimals === 0
            ? number_format($number, 0, ',', '.')
            : rtrim(rtrim(number_format($number, 3, ',', '.'), '0'), ',');
    }
}
