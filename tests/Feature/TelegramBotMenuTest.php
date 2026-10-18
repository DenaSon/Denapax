<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Testing\FakeNutgram;
use SergiX44\Nutgram\Telegram\Types\Chat\Chat;
use SergiX44\Nutgram\Telegram\Types\User\User;
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
            ->assertReplyText('به فروشگاه سوغات و خشکبار دنا خوش آمدید 🌿');

        $this->assertDatabaseHas('customers', [
            'telegram_user_id' => 123456,
            'telegram_chat_id' => 123456,
        ]);
    }
}
