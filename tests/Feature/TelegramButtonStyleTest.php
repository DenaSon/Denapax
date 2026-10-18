<?php

namespace Tests\Feature;

use App\Integrations\Telegram\StorefrontButtons;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton as Button;
use Tests\TestCase;

class TelegramButtonStyleTest extends TestCase
{
    public function test_native_button_styles_preserve_callback_and_unicode_fallbacks(): void
    {
        foreach ([
            [StorefrontButtons::primary('🌰 محصولات', 'menu:catalog'), 'primary', 'menu:catalog', '🌰 محصولات'],
            [StorefrontButtons::success('✅ تأیید و ثبت سفارش', 'checkout:start'), 'success', 'checkout:start', '✅ تأیید و ثبت سفارش'],
            [StorefrontButtons::danger('🗑️ حذف', 'cart:remove:1'), 'danger', 'cart:remove:1', '🗑️ حذف'],
        ] as [$button, $style, $callback, $label]) {
            $payload = json_decode(json_encode($button, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);

            $this->assertSame($style, $payload['style']);
            $this->assertSame($callback, $payload['callback_data']);
            $this->assertSame($label, $payload['text']);
            $this->assertArrayNotHasKey('icon_custom_emoji_id', $payload);
            $this->assertArrayNotHasKey('url', $payload);
        }
    }

    public function test_secondary_actions_stay_neutral_without_premium_icon_dependency(): void
    {
        $button = Button::make('🏠 منوی اصلی', callback_data: 'menu:home');
        $payload = json_decode(json_encode($button, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('menu:home', $payload['callback_data']);
        $this->assertSame('🏠 منوی اصلی', $payload['text']);
        $this->assertArrayNotHasKey('style', $payload);
        $this->assertArrayNotHasKey('icon_custom_emoji_id', $payload);
    }
}
