<?php

namespace App\Integrations\Telegram;

use SergiX44\Nutgram\Telegram\Types\Keyboard\KeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardMarkup;

final class StorefrontReplyKeyboard
{
    public const PRODUCTS = '🛍 محصولات';

    public const SEARCH = '🔎 جستجو';

    public const CART = '🛒 سبد خرید';

    public const ORDERS = '📦 سفارش‌های من';

    public const RECEIPT = '💳 پرداخت / ارسال رسید';

    public const SUPPORT = '💬 پشتیبانی';

    public const HELP = 'ℹ️ راهنما';

    public const HOME = '🏠 منوی اصلی';

    private const COMMANDS = [
        self::PRODUCTS => '/catalog',
        self::SEARCH => '/search',
        self::CART => '/cart',
        self::ORDERS => '/orders',
        self::RECEIPT => '/receipt',
        self::SUPPORT => '/support',
        self::HELP => '/help',
        self::HOME => '/start',
    ];

    public static function commandFor(string $text): ?string
    {
        return self::COMMANDS[$text] ?? null;
    }

    public static function make(): ReplyKeyboardMarkup
    {
        // This belongs below Telegram's message input. Inner-page action
        // buttons continue to use InlineKeyboardMarkup and callback data.
        return ReplyKeyboardMarkup::make(
            resize_keyboard: true,
            one_time_keyboard: false,
            is_persistent: true,
            input_field_placeholder: 'از منوی دنا انتخاب کنید',
        )
            ->addRow(KeyboardButton::make(self::PRODUCTS), KeyboardButton::make(self::SEARCH))
            ->addRow(KeyboardButton::make(self::CART), KeyboardButton::make(self::ORDERS))
            ->addRow(KeyboardButton::make(self::RECEIPT), KeyboardButton::make(self::SUPPORT))
            ->addRow(KeyboardButton::make(self::HELP), KeyboardButton::make(self::HOME));
    }
}
