<?php

namespace App\Support;

/**
 * Small, safe HTML fragments for Telegram's native HTML parse mode.
 * Escape everything that can be entered by a customer or store operator.
 */
final class TelegramHtml
{
    public static function escape(?string $text): string
    {
        return htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function bold(string $text): string
    {
        return '<b>'.self::escape($text).'</b>';
    }

    public static function quote(string $text): string
    {
        return '<blockquote>'.self::escape($text).'</blockquote>';
    }
}
