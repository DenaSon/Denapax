<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Persist all monetary amounts as integer IRR; present them as toman.
 * A single decimal digit round-trips preexisting prices ending in 1-9 IRR.
 */
final class Money
{
    public static function normalizeTomanInput(string $value): string
    {
        $value = strtr(trim($value), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        return preg_replace('/[,،٬\s]+/u', '', $value) ?? $value;
    }

    public static function tomanInput(int $irr): string
    {
        $whole = (string) intdiv($irr, 10);

        return $irr % 10 === 0 ? $whole : $whole.'.'.($irr % 10);
    }

    public static function tomanToIrr(string $toman): int
    {
        if (! preg_match('/^(0|[1-9][0-9]*)(?:\.([0-9]))?$/D', $toman, $matches)) {
            throw new InvalidArgumentException('Invalid toman amount.');
        }

        $whole = $matches[1];
        $fraction = (int) ($matches[2] ?? 0);
        $maxWhole = (string) intdiv(PHP_INT_MAX, 10);

        if (strlen($whole) > strlen($maxWhole)
            || (strlen($whole) === strlen($maxWhole) && strcmp($whole, $maxWhole) > 0)
            || ($whole === $maxWhole && $fraction > PHP_INT_MAX % 10)) {
            throw new InvalidArgumentException('Toman amount is too large.');
        }

        return (int) $whole * 10 + $fraction;
    }

    public static function formatToman(int $irr): string
    {
        $whole = number_format(intdiv($irr, 10));

        return $irr % 10 === 0 ? $whole : $whole.'.'.($irr % 10);
    }
}
