<?php

namespace Tests\Feature;

use App\Integrations\Telegram\StorefrontButtons;
use App\Integrations\Telegram\SupportContactLink;
use App\Models\StoreSetting;
use GuzzleHttp\Psr7\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ChatType;
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
                'text' => "🏔️ دنا؛ طعم اصالت، عطر طبیعت\n\nبه دنیای خوش‌طعم سوغات و خشکبار دنا خوش آمدید. 🌿\n\n🌰 برای شروع، از منوی زیر انتخاب کنید.",
                'reply_markup' => Keyboard::make()
                    ->addRow(StorefrontButtons::primary('🌰 محصولات', 'menu:catalog'), Button::make('🔎 جستجو', callback_data: 'menu:search'))
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

    public function test_private_start_with_hydrated_chat_type_enum_sends_menu(): void
    {
        // A real Telegram update is hydrated as ChatType::PRIVATE, unlike
        // older test factories that pass the literal string 'private'.
        /** @var FakeNutgram $bot */
        $bot = app(Nutgram::class);
        $bot->setCommonUser(User::make(123456, false, 'Ali'));
        $bot->setCommonChat(Chat::make(123456, ChatType::PRIVATE));

        $bot->hearText('/start')
            ->reply()
            ->assertCalled('sendMessage');

        $this->assertDatabaseHas('customers', [
            'telegram_user_id' => 123456,
            'telegram_chat_id' => 123456,
        ]);
    }

    public function test_private_callback_with_chat_type_enum_is_acknowledged_and_handled(): void
    {
        /** @var FakeNutgram $bot */
        $bot = app(Nutgram::class);
        $bot->setCommonUser(User::make(123456, false, 'Ali'));
        $bot->setCommonChat(Chat::make(123456, ChatType::PRIVATE));

        $bot->hearCallbackQueryData('menu:home')
            ->reply()
            ->assertCalled('answerCallbackQuery')
            ->assertCalled('sendMessage');
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

    public function test_support_opens_only_a_valid_telegram_username_and_provides_home_navigation(): void
    {
        StoreSetting::create([
            'store_name' => 'دنا',
            'support_contact' => '@DenaSupport',
            'card_number' => '6037991234567890',
            'card_holder_name' => 'Dena',
        ]);

        /** @var FakeNutgram $bot */
        $bot = app(Nutgram::class);
        $bot->setCommonUser(User::make(123456, false, 'Ali'));
        $bot->setCommonChat(Chat::make(123456, 'private'));

        $bot->hearText('/support')
            ->reply()
            ->assertReplyMessage([
                'text' => 'برای گفت‌وگوی مستقیم با پشتیبانی دنا، روی دکمه زیر بزنید.',
                'reply_markup' => Keyboard::make()
                    ->addRow(Button::make('💬 گفت‌وگو با پشتیبانی', url: 'https://t.me/DenaSupport'))
                    ->addRow(
                        Button::make('📖 راهنما', callback_data: 'menu:help'),
                        Button::make('🏠 منوی اصلی', callback_data: 'menu:home'),
                    ),
            ]);
    }

    public function test_support_with_non_telegram_contact_has_no_external_link_button(): void
    {
        StoreSetting::create([
            'store_name' => 'دنا',
            'support_contact' => '09123456789',
            'card_number' => '6037991234567890',
            'card_holder_name' => 'Dena',
        ]);

        /** @var FakeNutgram $bot */
        $bot = app(Nutgram::class);
        $bot->setCommonUser(User::make(123456, false, 'Ali'));
        $bot->setCommonChat(Chat::make(123456, 'private'));

        $bot->hearText('/support')
            ->reply()
            ->assertReplyMessage([
                'text' => "برای ارتباط با پشتیبانی دنا:\n09123456789",
                'reply_markup' => Keyboard::make()->addRow(
                    Button::make('📖 راهنما', callback_data: 'menu:help'),
                    Button::make('🏠 منوی اصلی', callback_data: 'menu:home'),
                ),
            ]);
    }

    public function test_support_url_allowlist_rejects_foreign_or_ambiguous_links(): void
    {
        $this->assertSame('https://t.me/DenaSupport', SupportContactLink::fromContact(' @DenaSupport '));
        $this->assertSame('https://t.me/DenaSupport', SupportContactLink::fromContact('https://t.me/DenaSupport/'));
        $this->assertSame('https://t.me/DenaSupport', SupportContactLink::fromContact('t.me/DenaSupport'));
        foreach ([
            '09123456789',
            'https://t.me.evil.example/DenaSupport',
            'https://t.me/+Abc123',
            'https://t.me/DenaSupport?start=spoof',
            '@ab',
            'https://evil.example',
        ] as $unsafe) {
            $this->assertNull(SupportContactLink::fromContact($unsafe));
        }
    }
}
