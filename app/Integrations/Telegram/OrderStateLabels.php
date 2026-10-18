<?php

namespace App\Integrations\Telegram;

/**
 * Customer-facing labels are intentionally distinct for order fulfillment
 * and payment/receipt review, even when order_status stays awaiting_payment.
 */
final class OrderStateLabels
{
    public static function order(string $state): string
    {
        return match ($state) {
            'awaiting_payment' => 'در انتظار پرداخت',
            'confirmed' => 'پرداخت تأیید شد؛ در صف آماده‌سازی',
            'preparing' => 'در حال آماده‌سازی',
            'shipped' => 'ارسال‌شده',
            'delivered' => 'تحویل‌شده',
            'canceled' => 'لغوشده',
            default => 'نامشخص',
        };
    }

    public static function payment(string $state): string
    {
        return match ($state) {
            'awaiting_receipt' => 'در انتظار ارسال رسید',
            'under_review' => 'رسید دریافت شد؛ در انتظار بررسی',
            'rejected' => 'رسید رد شد؛ نیازمند ارسال مجدد',
            'approved' => 'تأییدشده',
            default => 'نامشخص',
        };
    }
}
