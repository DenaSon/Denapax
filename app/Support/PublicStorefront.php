<?php

namespace App\Support;

final class PublicStorefront
{
    public static function telegramUrl(): ?string
    {
        // This is a PUBLIC username, not TELEGRAM_TOKEN. Never invent a link.
        $username = ltrim(trim((string) config('services.telegram.bot_username', '')), '@');

        return preg_match('/^[a-zA-Z][a-zA-Z0-9_]{4,31}$/D', $username)
            ? 'https://t.me/'.$username
            : null;
    }
}
