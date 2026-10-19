<?php

namespace Tests\Feature;

use App\Models\TelegramUpdate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use SergiX44\Nutgram\Nutgram;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_or_invalid_webhook_secret_is_rejected_without_processing(): void
    {
        config(['services.telegram.webhook_secret' => null]);
        $this->postJson('/api/telegram/webhook', ['update_id' => 42])->assertStatus(503);

        config(['services.telegram.webhook_secret' => 'phase-2-test-secret']);
        $this->postJson('/api/telegram/webhook', ['update_id' => 42], [
            'X-Telegram-Bot-Api-Secret-Token' => 'incorrect',
        ])->assertForbidden();

        $this->assertDatabaseCount('telegram_updates', 0);
    }

    public function test_authenticated_duplicate_update_runs_only_once(): void
    {
        config(['services.telegram.webhook_secret' => 'phase-2-test-secret']);

        $bot = Mockery::mock(Nutgram::class);
        $bot->shouldReceive('run')->once();
        $this->app->instance(Nutgram::class, $bot);

        $headers = ['X-Telegram-Bot-Api-Secret-Token' => 'phase-2-test-secret'];

        $this->postJson('/api/telegram/webhook', ['update_id' => 234], $headers)->assertOk();
        $this->postJson('/api/telegram/webhook', ['update_id' => 234], $headers)->assertOk()
            ->assertJsonPath('duplicate', true);

        $this->assertDatabaseCount('telegram_updates', 1);
        $this->assertSame('done', TelegramUpdate::findOrFail(234)->status);
    }

    public function test_failed_telegram_update_can_be_retried(): void
    {
        config(['services.telegram.webhook_secret' => 'phase-2-test-secret']);
        $bot = Mockery::mock(Nutgram::class);
        Log::spy();
        $bot->shouldReceive('run')->once()->andThrow(new \RuntimeException('https://api.telegram.org/botFAKE_SECRET/sendMessage'));
        $bot->shouldReceive('run')->once();
        $this->app->instance(Nutgram::class, $bot);
        $headers = ['X-Telegram-Bot-Api-Secret-Token' => 'phase-2-test-secret'];

        $this->postJson('/api/telegram/webhook', ['update_id' => 235], $headers)->assertStatus(503);
        $this->assertSame('failed', TelegramUpdate::findOrFail(235)->status);
        $this->assertSame(\RuntimeException::class, TelegramUpdate::findOrFail(235)->error_summary);
        Log::shouldHaveReceived('warning')->once()->with('Telegram webhook processing failed', [
            'exception_type' => \RuntimeException::class,
            'update_id' => 235,
        ]);

        $this->postJson('/api/telegram/webhook', ['update_id' => 235], $headers)->assertOk();
        $this->assertSame('done', TelegramUpdate::findOrFail(235)->status);
    }
}
