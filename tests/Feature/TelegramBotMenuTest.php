<?php

namespace Tests\Feature;

use App\Application\Actions\AddressConversation;
use App\Integrations\Telegram\StorefrontReplyKeyboard;
use App\Integrations\Telegram\SupportContactLink;
use App\Models\Customer;
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
                'reply_markup' => StorefrontReplyKeyboard::make(),
            ]);

        $this->assertDatabaseHas('customers', [
            'telegram_user_id' => 123456,
            'telegram_chat_id' => 123456,
        ]);
    }

    public function test_reply_keyboard_is_persistent_and_each_label_maps_to_existing_navigation(): void
    {
        $keyboard = StorefrontReplyKeyboard::make()->jsonSerialize();

        $this->assertTrue($keyboard['is_persistent']);
        $this->assertTrue($keyboard['resize_keyboard']);
        $this->assertFalse($keyboard['one_time_keyboard']);
        $this->assertCount(4, $keyboard['keyboard']);

        $actions = [
            StorefrontReplyKeyboard::PRODUCTS => '/catalog',
            StorefrontReplyKeyboard::SEARCH => '/search',
            StorefrontReplyKeyboard::CART => '/cart',
            StorefrontReplyKeyboard::ORDERS => '/orders',
            StorefrontReplyKeyboard::RECEIPT => '/receipt',
            StorefrontReplyKeyboard::SUPPORT => '/support',
            StorefrontReplyKeyboard::HELP => '/help',
            StorefrontReplyKeyboard::HOME => '/start',
        ];

        $this->assertSame(
            array_keys($actions),
            array_merge(...array_map(
                fn (array $row): array => array_map(fn ($button): string => $button->text, $row),
                $keyboard['keyboard'],
            )),
        );

        foreach ($actions as $label => $command) {
            $this->assertSame($command, StorefrontReplyKeyboard::commandFor($label));
        }

        $this->assertNull(StorefrontReplyKeyboard::commandFor('یک متن معمولی'));
    }

    public function test_search_reply_button_interrupts_address_form_and_starts_search(): void
    {
        /** @var FakeNutgram $bot */
        $bot = app(Nutgram::class);
        $bot->setCommonUser(User::make(123456, false, 'Ali'));
        $bot->setCommonChat(Chat::make(123456, ChatType::PRIVATE));
        $bot->hearText('/start')->reply();

        $customer = Customer::where('telegram_user_id', 123456)->firstOrFail();
        app(AddressConversation::class)->start($customer);
        $this->assertSame('address:recipient_name', app(AddressConversation::class)->session($customer)->state);

        $bot->hearText(StorefrontReplyKeyboard::SEARCH)
            ->reply()
            ->assertReplyText('🔎 نام یا بخشی از توضیحات محصول را بنویسید.');

        $this->assertSame('search', app(AddressConversation::class)->session($customer)->fresh()->state);
    }

    public function test_receipt_reply_button_still_opens_inline_order_selection(): void
    {
        /** @var FakeNutgram $bot */
        $bot = app(Nutgram::class);
        $bot->setCommonUser(User::make(123456, false, 'Ali'));
        $bot->setCommonChat(Chat::make(123456, ChatType::PRIVATE));

        $bot->hearText(StorefrontReplyKeyboard::RECEIPT)
            ->reply()
            ->assertReplyMessage([
                'text' => 'سفارش پرداخت‌نشده‌ای برای ارسال رسید ندارید.',
                'reply_markup' => Keyboard::make()->addRow(
                    Button::make('📦 سفارش‌های من', callback_data: 'menu:orders'),
                    Button::make('🏠 منوی اصلی', callback_data: 'menu:home'),
                ),
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
