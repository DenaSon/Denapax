<?php

namespace App\Integrations\Telegram;

use SergiX44\Nutgram\Telegram\Properties\ButtonStyle;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton as Button;

/**
 * Native Telegram button emphasis. Neutral actions use Button::make directly.
 *
 * Telegram controls the actual client colors and rendering; keep readable
 * Persian labels/Unicode emoji so actions never depend on color or Premium icons.
 */
final class StorefrontButtons
{
    public static function primary(string $label, string $callback): Button
    {
        return Button::make($label, callback_data: $callback, style: ButtonStyle::PRIMARY);
    }

    public static function success(string $label, string $callback): Button
    {
        return Button::make($label, callback_data: $callback, style: ButtonStyle::SUCCESS);
    }

    public static function danger(string $label, string $callback): Button
    {
        return Button::make($label, callback_data: $callback, style: ButtonStyle::DANGER);
    }
}
