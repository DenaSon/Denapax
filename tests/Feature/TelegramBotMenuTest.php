<?php

namespace Tests\Feature;

use GuzzleHttp\Psr7\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Chat\Chat;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton as Button;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup as Keyboard;
use SergiX44\Nutgram\Telegram\Types\User\User;
use SergiX44\Nutgram\Testing\FakeNutgram;
use Tests\TestCase;

class TelegramBotMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_start_command_registers_customer_and_sends_main_menu(): void
    {
        /** @var FakeNutgram $bot */
        $bot = app(Nutgram::class);
        $bot->setCommonUser(User::make(123456, false, 'Ali'));
        $bot->setCommonChat(Chat::make(123456, 'private'));

        $bot->hearText('/start')
            ->reply()
            ->assertReplyMessage([
                'text' => 'به فروشگاه سوغات و خشکبار دنا خوش آمدید 🌿',
                'reply_markup' => Keyboard::make()
                    ->addRow(Button::make('🌰 محصولات', callback_data: 'menu:catalog'), Button::make('🔎 جستجو', callback_data: 'menu:search'))
                    ->addRow(Button::make('📍 نشانی‌های من', callback_data: 'menu:addresses'))
                    ->addRow(Button::make('🧾 ارسال رسید', callback_data: 'menu:receipt'))
                    ->addRow(Button::make('🛒 سبد خرید', callback_data: 'menu:cart'), Button::make('📦 سفارش‌های من', callback_data: 'menu:orders'))
                    ->addRow(
                        Button::make('📖 راهنما', callback_data: 'menu:help'),
                        Button::make('☎️ پشتیبانی', callback_data: 'menu:support'),
                    ),
            ]);

        $this->assertDatabaseHas('customers', [
            'telegram_user_id' => 123456,
            'telegram_chat_id' => 123456,
        ]);
    }

    public function test_help_command_explains_order_payment_and_tracking_with_navigation(): void
    {
        /** @var FakeNutgram $bot */
        $bot = app(Nutgram::class);
        $bot->setCommonUser(User::make(123456, false, 'Ali'));
        $bot->setCommonChat(Chat::make(123456, 'private'));

        $bot->hearText('/help')
            ->reply()
            ->assertRaw(function (Request $request): bool {
                $payload = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
                $text = (string) ($payload['text'] ?? '');
                $buttons = $payload['reply_markup']['inline_keyboard'] ?? [];

                return str_contains($text, 'راهنمای خرید')
                    && str_contains($text, 'ابتدا سفارش مربوط را انتخاب')
                    && str_contains($text, 'بارگذاری رسید به‌معنی تأیید پرداخت نیست')
                    && str_contains($text, 'کد رهگیری')
                    && ($buttons[2][0]['callback_data'] ?? '') === 'menu:home';
            });
    }

    public function test_help_menu_callback_is_handled(): void
    {
        /** @var FakeNutgram $bot */
        $bot = app(Nutgram::class);
        $bot->setCommonUser(User::make(123456, false, 'Ali'));
        $bot->setCommonChat(Chat::make(123456, 'private'));

        $bot->hearCallbackQueryData('menu:help')
            ->reply()
            ->assertCalled('answerCallbackQuery')
            ->assertCalled('sendMessage');
    }
}
