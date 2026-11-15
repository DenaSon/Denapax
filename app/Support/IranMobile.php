<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

final class IranMobile
{
    public static function normalize(string $value): string
    {
        $value = strtr(trim($value), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        // Canonical domestic format; no ambiguous or unverified aliases.
        if (str_starts_with($value, '+98')) {
            $value = '0'.substr($value, 3);
        } elseif (str_starts_with($value, '98') && strlen($value) === 12) {
            $value = '0'.substr($value, 2);
        }

        if (! preg_match('/^09[0-9]{9}$/D', $value)) {
            throw ValidationException::withMessages(['mobile' => 'شماره موبایل معتبر وارد کنید.']);
        }

        return $value;
    }
}
