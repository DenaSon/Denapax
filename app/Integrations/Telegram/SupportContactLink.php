<?php

namespace App\Integrations\Telegram;

final class SupportContactLink
{
    public static function fromContact(string $contact): ?string
    {
        $contact = trim($contact);

        // Only an explicit Telegram username or a direct t.me username URL
        // is turned into a button. Free text, phone numbers, invite links and
        // arbitrary external hosts stay as plain contact information.
        if (preg_match('~\A@([A-Za-z][A-Za-z0-9_]{4,31})\z~', $contact, $matches)) {
            return 'https://t.me/'.$matches[1];
        }

        if (preg_match('~\A(?:https?://)?(?:www\.)?t\.me/([A-Za-z][A-Za-z0-9_]{4,31})/?\z~i', $contact, $matches)) {
            return 'https://t.me/'.$matches[1];
        }

        return null;
    }
}
