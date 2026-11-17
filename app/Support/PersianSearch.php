<?php

namespace App\Support;

final class PersianSearch
{
    private const REPLACEMENTS = [
        'آ' => 'ا', 'أ' => 'ا', 'إ' => 'ا',
        'ي' => 'ی', 'ى' => 'ی', 'ك' => 'ک',
        '‌' => '', ' ' => '', "\t" => '',
        'َ' => '', 'ِ' => '', 'ُ' => '', 'ً' => '',
        'ٍ' => '', 'ٌ' => '', 'ْ' => '', 'ّ' => '',
        'ـ' => '',
    ];

    public static function normalize(string $input): string
    {
        return mb_strtolower(strtr(trim($input), self::REPLACEMENTS));
    }

    /**
     * Split a query into independent search terms before removing whitespace.
     * Every term must match at least one searchable product or category field.
     */
    public static function terms(string $input): array
    {
        $input = str_replace(['%', '_'], '', trim($input));
        $parts = preg_split('/\s+/u', $input, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $terms = [];

        foreach ($parts as $part) {
            $term = self::normalize($part);

            if ($term !== '' && ! in_array($term, $terms, true)) {
                $terms[] = $term;
            }
        }

        return $terms;
    }

    /** Column is a hardcoded code-defined identifier, never supplied from a request. */
    public static function sql(string $column): string
    {
        if (! in_array($column, ['title', 'description', 'summary', 'slug', 'name'], true)) {
            throw new \InvalidArgumentException('Unsupported catalog search column');
        }

        $expression = $column;
        foreach (self::REPLACEMENTS as $from => $to) {
            if ($from === "\t") {
                continue;
            }

            $expression = "REPLACE($expression, '$from', '$to')";
        }

        return "LOWER($expression)";
    }
}
