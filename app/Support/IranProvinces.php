<?php

namespace App\Support;

final class IranProvinces
{
    /**
     * The 31 Iranian provinces. Keep names here rather than requiring a
     * package, database seed, external request, or customer-side location API.
     *
     * @var list<string>
     */
    private const NAMES = [
        'آذربایجان شرقی',
        'آذربایجان غربی',
        'اردبیل',
        'اصفهان',
        'البرز',
        'ایلام',
        'بوشهر',
        'تهران',
        'چهارمحال و بختیاری',
        'خراسان جنوبی',
        'خراسان رضوی',
        'خراسان شمالی',
        'خوزستان',
        'زنجان',
        'سمنان',
        'سیستان و بلوچستان',
        'فارس',
        'قزوین',
        'قم',
        'کردستان',
        'کرمان',
        'کرمانشاه',
        'کهگیلویه و بویراحمد',
        'گلستان',
        'گیلان',
        'لرستان',
        'مازندران',
        'مرکزی',
        'هرمزگان',
        'همدان',
        'یزد',
    ];

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return self::NAMES;
    }

    public static function contains(string $value): bool
    {
        return in_array(self::normalize($value), self::NAMES, true);
    }

    /**
     * Resolve older Arabic-yeh/kaf and spacing variants to the canonical
     * Persian label without silently replacing an unrecognized old address.
     */
    public static function normalize(string $value): string
    {
        $candidate = self::comparisonKey($value);

        foreach (self::NAMES as $province) {
            if (self::comparisonKey($province) === $candidate) {
                return $province;
            }
        }

        // Do not rewrite unrecognized legacy values: display them exactly as
        // saved so the customer can explicitly replace the province.
        return trim($value);
    }

    private static function comparisonKey(string $value): string
    {
        $value = str_replace(
            ['ي', 'ى', 'ك', "\u{200C}", "\u{200D}", "\u{200E}", "\u{200F}"],
            ['ی', 'ی', 'ک', ' ', ' ', ' ', ' '],
            $value,
        );

        return preg_replace('/\\s+/u', ' ', trim($value)) ?? trim($value);
    }
}
