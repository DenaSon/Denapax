<?php

namespace App\Application\Actions;

use App\Integrations\Payments\ZarinpalGateway;
use App\Models\GatewayPaymentAttempt;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Throwable;

class ReconcileGatewayPayment
{
    public function __construct(
        private readonly ZarinpalGateway $gateway,
        private readonly VerifyGatewayPayment $verify,
    ) {}

    public function inquire(User $reviewer, int $attemptId): GatewayPaymentAttempt
    {
        $this->authorize($reviewer);

        $attempt = GatewayPaymentAttempt::query()->findOrFail($attemptId);

        if ($attempt->authority === null) {
            throw ValidationException::withMessages([
                'payment' => 'این درخواست Authority ندارد؛ وضعیت آن باید در پنل پذیرنده بررسی شود.',
            ]);
        }

        try {
            $status = $this->gateway->inquire($attempt->authority);
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'payment' => 'استعلام زرین‌پال در دسترس نیست. تا مشخص‌شدن نتیجه، اجازه پرداخت مجدد ندهید.',
            ]);
        }

        DB::transaction(function () use ($attempt, $status): void {
            $order = Order::query()->whereKey($attempt->order_id)->lockForUpdate()->firstOrFail();
            $locked = GatewayPaymentAttempt::query()->whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            $locked->update([
                'inquiry_status' => $status,
                'inquiry_checked_at' => now(),
            ]);
        }, 3);

        // PAID / VERIFIED means the provider may already have collected money.
        // The only way to confirm the order is the regular server-side verify.
        if (in_array($status, ['PAID', 'VERIFIED'], true)) {
            return $this->verify->execute($attempt->authority) ?? $attempt->fresh();
        }

        return $attempt->fresh();
    }

    public function clearUnpaid(User $reviewer, int $attemptId, string $note): GatewayPaymentAttempt
    {
        $this->authorize($reviewer);
        $note = trim($note);

        if (mb_strlen($note) < 20 || mb_strlen($note) > 1000) {
            throw ValidationException::withMessages([
                'resolutionNote' => 'شماره مستند تطبیق بانکی و توضیح تصمیم را وارد کنید (۲۰ تا ۱۰۰۰ نویسه).',
            ]);
        }

        return DB::transaction(function () use ($reviewer, $attemptId, $note): GatewayPaymentAttempt {
            $target = GatewayPaymentAttempt::query()->findOrFail($attemptId);
            $order = Order::query()->whereKey($target->order_id)->lockForUpdate()->firstOrFail();
            $attempt = GatewayPaymentAttempt::query()->whereKey($attemptId)->lockForUpdate()->firstOrFail();

            if ($order->order_status !== 'awaiting_payment'
                || ! in_array($order->payment_status, ['awaiting_receipt', 'rejected'], true)
                || in_array($attempt->status, ['verified', 'requires_review', 'cleared_unpaid', 'verifying'], true)
                || $order->receipts()->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages([
                    'payment' => 'این تراکنش اکنون امکان ثبت تصمیم «واریز نشده» ندارد.',
                ]);
            }

            if ($attempt->authority !== null
                && (! in_array($attempt->inquiry_status, ['FAILED', 'REVERSED'], true)
                    || $attempt->inquiry_checked_at === null
                    || $attempt->inquiry_checked_at->lessThan(now()->subMinutes(5)))) {
                throw ValidationException::withMessages([
                    'payment' => 'ابتدا وضعیت ناموفق یا برگشت‌خورده را با استعلام تازه زرین‌پال تأیید کنید.',
                ]);
            }

            if ($attempt->authority === null
                && (! in_array($attempt->status, ['request_unknown', 'failed', 'initializing', 'expired'], true)
                    || $attempt->created_at->greaterThan(now()->subHour()))) {
                throw ValidationException::withMessages([
                    'payment' => 'درخواست بدون Authority باید حداقل یک ساعت منتظر بماند و سپس با گزارش پنل پذیرنده تطبیق داده شود.',
                ]);
            }

            $attempt->update([
                'status' => 'cleared_unpaid',
                'resolved_by' => $reviewer->id,
                'resolved_at' => now(),
                'resolution_note' => $note,
                'verification_token' => null,
                'last_error' => null,
            ]);

            activity()->causedBy($reviewer)->performedOn($attempt)
                ->withProperties([
                    'order_id' => $order->id,
                    'attempt_id' => $attempt->id,
                    'decision' => 'cleared_unpaid',
                    'inquiry_status' => $attempt->inquiry_status,
                ])
                ->log('payment.gateway_reconciled');

            return $attempt->refresh();
        }, 3);
    }

    private function authorize(User $reviewer): void
    {
        Gate::forUser($reviewer)->authorize('payments.review');
        abort_unless($reviewer->is_active, 403);
    }
}
