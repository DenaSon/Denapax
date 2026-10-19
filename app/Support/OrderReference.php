<?php

namespace App\Support;

final class OrderReference
{
    private const MODULUS = 900000000;

    private const MULTIPLIER = 7919;

    private const OFFSET = 1234567;

    private const INVERSE = 140017679;

    public static function forId(int $id): string
    {
        if ($id < 1 || $id >= self::MODULUS) {
            throw new \InvalidArgumentException('Order ID outside public reference range');
        }

        return 'DN-'.(100000000 + (($id * self::MULTIPLIER + self::OFFSET) % self::MODULUS));
    }

    public static function toId(string $reference): ?int
    {
        $reference = strtoupper(trim($reference));
        if (! preg_match('/^DN-([1-9][0-9]{8})$/', $reference, $matches)) {
            return null;
        }

        $number = (int) $matches[1] - 100000000;
        $encoded = ($number - self::OFFSET + self::MODULUS) % self::MODULUS;
        $id = ($encoded * self::INVERSE) % self::MODULUS;

        return $id > 0 ? $id : null;
    }
}
