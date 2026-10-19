<?php

namespace App\Support;

final class AdminGroupChat
{
    public static function id(): ?int
    {
        $value = trim((string) config('services.telegram.admin_chat_id', ''));

        // Telegram private group/supergroup chat identifiers are negative.
        // Require a real configured ID; never fall back to a customer chat.
        if (! preg_match('/^-[1-9][0-9]{4,17}$/', $value)) {
            return null;
        }

        $id = filter_var($value, FILTER_VALIDATE_INT);

        return $id === false ? null : $id;
    }
}
