<?php

namespace Tests\Feature;

use App\Application\Notifications\CustomerNotificationText;
use App\Jobs\DeliverOutboxNotification;
use App\Livewire\Admin\NotificationLogPage;
use App\Models\Customer;
use App\Models\NotificationLog;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class OutboxNotificationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_dispatcher_claims_an_event_once_while_the_database_queue_worker_is_offline(): void
    {
        [$eventId] = $this->orderAndEvent();
        Queue::fake();

        $this->artisan('denapax:notifications:dispatch')->assertExitCode(0);
        $this->artisan('denapax:notifications:dispatch')->assertExitCode(0);

        $log = NotificationLog::query()->sole();

        $this->assertSame('web', $log->channel);
        $this->assertSame('queued', $log->status);
        $this->assertSame(0, $log->attempts);
        $this->assertNotNull($log->claimed_at);
        $this->assertTrue($log->next_attempt_at->isFuture());

        Queue::assertPushed(DeliverOutboxNotification::class, 1);
        Queue::assertPushed(DeliverOutboxNotification::class, fn (DeliverOutboxNotification $job): bool => $job->eventId === $eventId);
    }

    public function test_terminal_failure_is_not_requeued_by_the_scheduler(): void
    {
        [$eventId, $order] = $this->orderAndEvent();
        NotificationLog::query()->create([
            'outbox_event_id' => $eventId,
            'customer_id' => $order->customer_id,
            'channel' => 'web',
            'status' => 'failed',
            'attempts' => 3,
            'last_error' => 'RuntimeException: formatter unavailable',
        ]);
        Queue::fake();

        $this->artisan('denapax:notifications:dispatch')->assertExitCode(0);

        Queue::assertNothingPushed();
        $this->assertDatabaseHas('notification_logs', [
            'outbox_event_id' => $eventId,
            'status' => 'failed',
            'attempts' => 3,
        ]);
    }

    public function test_delivery_failure_uses_bounded_backoff_then_becomes_visible_as_terminal_failure(): void
    {
        [$eventId] = $this->orderAndEvent();

        $formatter = Mockery::mock(CustomerNotificationText::class);
        $formatter->shouldReceive('render')
            ->times(3)
            ->andThrow(new RuntimeException('formatter unavailable'));

        foreach ([1, 2, 3] as $attempt) {
            $job = new DeliverOutboxNotification($eventId);
            $queueJob = Mockery::mock(\Illuminate\Contracts\Queue\Job::class);
            $queueJob->shouldReceive('attempts')->andReturn($attempt);
            $job->setJob($queueJob);

            try {
                $job->handle($formatter);
                $this->fail('The formatter failure should be propagated to Laravel queue retries.');
            } catch (RuntimeException $exception) {
                $log = NotificationLog::query()->sole();

                $this->assertSame($attempt, $log->attempts);
                $this->assertSame('formatter unavailable', $exception->getMessage());

                if ($attempt < 3) {
                    $this->assertSame('queued', $log->status);
                    $this->assertTrue($log->next_attempt_at->isFuture());
                    $this->assertNotNull($log->last_error);
                } else {
                    $job->failed($exception);
                }
            }
        }

        $this->assertDatabaseHas('notification_logs', [
            'outbox_event_id' => $eventId,
            'status' => 'failed',
            'attempts' => 3,
            'last_error' => 'RuntimeException: formatter unavailable',
            'claimed_at' => null,
            'next_attempt_at' => null,
        ]);
        $this->assertDatabaseCount('customer_notifications', 0);
        $this->assertNull(\Illuminate\Support\Facades\DB::table('outbox_events')->where('id', $eventId)->value('processed_at'));
    }

    public function test_authorized_staff_can_manually_retry_a_failed_web_notification(): void
    {
        [$eventId, $order] = $this->orderAndEvent();
        $log = NotificationLog::query()->create([
            'outbox_event_id' => $eventId,
            'customer_id' => $order->customer_id,
            'channel' => 'web',
            'status' => 'failed',
            'attempts' => 3,
            'last_error' => 'RuntimeException: formatter unavailable',
        ]);
        $staff = User::factory()->create();
        $staff->assignRole('super-admin');
        Queue::fake();

        Livewire::actingAs($staff, 'web')
            ->test(NotificationLogPage::class)
            ->call('retry', $log->id)
            ->assertHasNoErrors();

        $log->refresh();

        $this->assertSame('queued', $log->status);
        $this->assertSame(3, $log->attempts, 'Manual retry preserves lifetime attempt history.');
        $this->assertSame('RuntimeException: formatter unavailable', $log->last_error);
        $this->assertNotNull($log->next_attempt_at);
        Queue::assertPushedOn('notifications', DeliverOutboxNotification::class, 1);
    }

    public function test_staff_without_order_update_permission_cannot_manually_retry(): void
    {
        [$eventId, $order] = $this->orderAndEvent();
        $log = NotificationLog::query()->create([
            'outbox_event_id' => $eventId,
            'customer_id' => $order->customer_id,
            'channel' => 'web',
            'status' => 'failed',
            'attempts' => 3,
        ]);
        $staff = User::factory()->create();
        $role = \Spatie\Permission\Models\Role::findOrCreate('notification-viewer', 'web');
        $role->givePermissionTo('orders.view');
        $staff->assignRole($role);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        Livewire::actingAs($staff, 'web')
            ->test(NotificationLogPage::class)
            ->call('retry', $log->id);
    }

    private function orderAndEvent(): array
    {
        $customer = Customer::create(['mobile' => '09120000031', 'mobile_verified_at' => now()]);
        $order = Order::create([
            'order_number' => 'DN-WEB-1',
            'checkout_key' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'order_status' => 'awaiting_payment',
            'payment_status' => 'awaiting_receipt',
            'recipient_name' => 'Dena Buyer',
            'recipient_mobile' => '09120000031',
            'province' => 'Kohgiluyeh',
            'city' => 'Sisakht',
            'address_line' => 'Example test address 12',
            'postal_code' => '1234567890',
            'goods_subtotal_irr' => 1500000,
            'shipping_fee_irr' => 250000,
            'total_irr' => 1750000,
            'shipping_weight_grams' => 700,
            'shipping_rule_snapshot' => [],
            'payment_instructions_snapshot' => [],
        ]);
        $eventId = (string) Str::uuid();

        \Illuminate\Support\Facades\DB::table('outbox_events')->insert([
            'id' => $eventId,
            'aggregate_type' => 'order',
            'aggregate_id' => $order->id,
            'event_type' => 'order.created',
            'payload' => json_encode(['order_id' => $order->id, 'customer_id' => $customer->id], JSON_THROW_ON_ERROR),
            'occurred_at' => now(),
        ]);

        return [$eventId, $order];
    }
}
