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

    /** Column is a hardcoded code-defined identifier, never supplied from a request. */
    public static function sql(string $column): string
    {
        if (! in_array($column, ['title', 'description', 'summary'], true)) {
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
