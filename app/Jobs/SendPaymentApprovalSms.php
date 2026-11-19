<?php

namespace App\Jobs;

use App\Integrations\Sms\MedianaPaymentApprovalSmsSender;
use App\Models\NotificationLog;
use App\Models\Order;
use App\Models\PaymentReceipt;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

final class SendPaymentApprovalSms implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    // SMS delivery is not idempotent at the provider. Never automatically
    // retry an ambiguous timeout or crash after starting the API request.
    public int $tries = 1;

    public int $timeout = 25;

    public int $uniqueFor = 604800;

    public function __construct(public readonly int $notificationLogId) {}

    public function uniqueId(): string
    {
        return 'payment-approval-sms:'.$this->notificationLogId;
    }

    public function handle(MedianaPaymentApprovalSmsSender $sender): void
    {
        if (! config('services.mediana.payment_approval_sms_enabled', false)) {
            return;
        }

        // The DB state transition is atomic. A duplicate queued job cannot
        // send the same event twice; only the initial attempt reaches the API.
        $recipient = DB::transaction(function (): ?array {
            $log = NotificationLog::query()->whereKey($this->notificationLogId)
                ->lockForUpdate()->first();

            if (! $log || $log->channel !== 'sms' || $log->status !== 'queued'
                || $log->attempts !== 0) {
                return null;
            }

            $event = DB::table('outbox_events')->where('id', $log->outbox_event_id)->first();
            $payload = $event ? json_decode($event->payload, true, 512, JSON_THROW_ON_ERROR) : [];

            $order = Order::query()->with('customer')->find((int) ($payload['order_id'] ?? 0));
            $receipt = PaymentReceipt::query()->find((int) ($payload['receipt_id'] ?? 0));

            if (! $event || $event->event_type !== 'payment.approved'
                || $event->aggregate_type !== 'payment_receipt'
                || ! $order || ! $receipt || ! $order->customer
                || $receipt->order_id !== $order->id
                || $event->aggregate_id !== $receipt->id
                || $receipt->status !== 'approved'
                || $order->payment_status !== 'approved'
                || (int) $order->customer_id !== (int) $log->customer_id
                || (int) ($payload['customer_id'] ?? 0) !== (int) $log->customer_id) {
                $log->update([
                    'status' => 'failed',
                    'attempts' => 1,
                    'claimed_at' => null,
                    'next_attempt_at' => null,
                    'last_error' => 'Invalid payment approval SMS event or recipient.',
                ]);

                return null;
            }

            $log->update([
                'status' => 'sending',
                'attempts' => 1,
                'claimed_at' => now(),
                'next_attempt_at' => null,
                'last_error' => null,
            ]);

            // Always notify the account login mobile, never delivery recipient.
            return [$order->customer->mobile, $order->public_reference];
        }, 3);

        if ($recipient === null) {
            return;
        }

        try {
            $sender->send(...$recipient);

            NotificationLog::query()
                ->whereKey($this->notificationLogId)
                ->where('channel', 'sms')
                ->where('status', 'sending')
                ->update([
                    'status' => 'sent',
                    'claimed_at' => null,
                    'next_attempt_at' => null,
                    'sent_at' => now(),
                    'last_error' => null,
                ]);
        } catch (Throwable $exception) {
            // Deliberately terminal: an ambiguous request must not cause a
            // duplicate payment-confirmation SMS on a queue retry.
            NotificationLog::query()
                ->whereKey($this->notificationLogId)
                ->where('channel', 'sms')
                ->where('status', 'sending')
                ->update([
                    'status' => 'failed',
                    'claimed_at' => null,
                    'next_attempt_at' => null,
                    'last_error' => 'Mediana approval SMS send failed; inspect provider logs.',
                ]);

            throw $exception;
        }
    }
}
