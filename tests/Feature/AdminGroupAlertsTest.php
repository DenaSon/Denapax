<?php

namespace Tests\Feature;

use App\Application\Notifications\AdminGroupAlertText;
use App\Jobs\DeliverAdminGroupAlert;
use App\Models\AdminGroupAlertLog;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;
use SergiX44\Nutgram\Nutgram;
use Tests\TestCase;

class AdminGroupAlertsTest extends TestCase
{
    use RefreshDatabase;

    public function test_group_is_optional_and_activation_skips_history_but_delivers_all_three_new_events_once(): void
    {
        $old = $this->event('order.created');
        Bus::fake();
        Artisan::call('denapax:admin-alerts:dispatch');
        Bus::assertNotDispatched(DeliverAdminGroupAlert::class);

        config(['services.telegram.admin_chat_id' => '-1001234567890', 'nutgram.token' => 'test-token']);
        Artisan::call('denapax:admin-alerts:dispatch');
        Bus::assertNotDispatched(DeliverAdminGroupAlert::class);
        $this->assertDatabaseMissing('admin_group_alert_logs', ['outbox_event_id' => $old['id']]);

        $this->travel(2)->seconds();
        $events = [
            $this->event('order.created'),
            $this->event('payment.receipt_submitted'),
            $this->event('payment.approved'),
        ];
        Artisan::call('denapax:admin-alerts:dispatch');
        Bus::assertDispatchedTimes(DeliverAdminGroupAlert::class, 3);

        $bot = Mockery::mock(Nutgram::class);
        $bot->shouldReceive('sendMessage')->times(3)->andReturnNull();
        foreach ($events as $event) {
            (new DeliverAdminGroupAlert($event['id']))->handle($bot, app(AdminGroupAlertText::class));
        }

        $this->assertSame(3, AdminGroupAlertLog::where('status', 'sent')->count());
        $this->assertNull(DB::table('outbox_events')->where('id', $events[0]['id'])->value('processed_at'));
        $this->assertDatabaseCount('notification_logs', 0);
        // Replayed job must never send a second time after success.
        (new DeliverAdminGroupAlert($events[0]['id']))->handle($bot, app(AdminGroupAlertText::class));
        $this->travelBack();
    }

    public function test_failed_group_delivery_retries_independently_without_exposing_sensitive_values(): void
    {
        config(['services.telegram.admin_chat_id' => '-1001234567890', 'nutgram.token' => 'test-token']);
        Artisan::call('denapax:admin-alerts:dispatch');
        $this->travel(2)->seconds();
        $event = $this->event('payment.receipt_submitted');
        $formatter = app(AdminGroupAlertText::class);

        $message = $formatter->render('payment.receipt_submitted', $event['order']);
        $this->assertStringContainsString($event['order']->public_reference, $message);
        $this->assertStringContainsString('12,000 تومان', $message);
        $this->assertStringNotContainsString('6037991234567890', $message);
        $this->assertStringNotContainsString('09123456789', $message);
        $this->assertStringNotContainsString('Sensitive', $message);
        $button = $formatter->keyboard('payment.receipt_submitted', $event['order'], $event['receipt'])->jsonSerialize();
        $this->assertStringContainsString('receipt='.$event['receipt']->id, $button['inline_keyboard'][0][0]->url);
        $this->assertStringContainsString('/admin/payments', $button['inline_keyboard'][0][0]->url);

        $bot = Mockery::mock(Nutgram::class);
        $bot->shouldReceive('sendMessage')->once()->andThrow(new \RuntimeException('a confidential provider error'));
        $bot->shouldReceive('sendMessage')->once()->andReturnNull();

        $job = new DeliverAdminGroupAlert($event['id']);
        $job->handle($bot, $formatter);
        $log = AdminGroupAlertLog::firstOrFail();
        $this->assertSame('failed', $log->status);
        $this->assertSame('RuntimeException', $log->last_error);
        $this->assertSame(1, $log->attempts);

        $this->travel(3)->minutes();
        $job->handle($bot, $formatter);
        $this->assertSame('sent', $log->fresh()->status);
        $this->assertSame(2, $log->fresh()->attempts);
        $this->travelBack();
    }

    public function test_specific_receipt_review_link_stays_protected_by_admin_authorization(): void
    {
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
        $first = $this->event('payment.receipt_submitted');
        $second = $this->event('payment.receipt_submitted');

        $link = route('admin.payments', ['receipt' => $first['receipt']->id]);
        $this->get($link)->assertRedirect('/login');

        $reviewer = User::factory()->create();
        $reviewer->assignRole('super-admin');
        $this->actingAs($reviewer)->get($link)
            ->assertOk()
            ->assertSee($first['order']->public_reference)
            ->assertDontSee($second['order']->public_reference);

        $unauthorized = User::factory()->create();
        $this->actingAs($unauthorized)->get($link)->assertForbidden();
    }

    private function event(string $type): array
    {
        $customer = Customer::create([
            'telegram_user_id' => random_int(100000, 999999999),
            'telegram_chat_id' => 12345,
        ]);
        $order = Order::create([
            'order_number' => 'DN-'.Str::upper(Str::random(16)),
            'checkout_key' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'order_status' => 'awaiting_payment',
            'payment_status' => 'under_review',
            'recipient_name' => 'Sensitive Buyer',
            'recipient_mobile' => '09123456789',
            'province' => 'Tehran',
            'city' => 'Tehran',
            'address_line' => 'Sensitive home address',
            'postal_code' => '1234567890',
            'goods_subtotal_irr' => 100000,
            'shipping_fee_irr' => 20000,
            'total_irr' => 120000,
            'shipping_weight_grams' => 300,
            'shipping_rule_snapshot' => ['base_weight_grams' => 1000],
            'payment_instructions_snapshot' => [
                'card_number' => '6037991234567890', 'card_holder_name' => 'Sensitive Cardholder',
            ],
        ]);
        $receipt = null;
        if ($type !== 'order.created') {
            $receipt = $order->receipts()->create([
                'attempt_number' => 1,
                'storage_path' => 'payment-receipts/'.Str::uuid().'.png',
                'status' => 'pending',
                'submitted_at' => now(),
            ]);
        }

        $id = (string) Str::uuid();
        DB::table('outbox_events')->insert([
            'id' => $id,
            'aggregate_type' => $receipt ? 'payment_receipt' : 'order',
            'aggregate_id' => $receipt?->id ?? $order->id,
            'event_type' => $type,
            'payload' => json_encode([
                'order_id' => $order->id,
                'customer_id' => $customer->id,
                'receipt_id' => $receipt?->id,
            ], JSON_THROW_ON_ERROR),
            'occurred_at' => now(),
        ]);

        return compact('id', 'order', 'receipt');
    }
}
