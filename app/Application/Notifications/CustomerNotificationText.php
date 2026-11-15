<?php

namespace App\Application\Notifications;

use App\Models\Order;
use App\Models\PaymentReceipt;

class CustomerNotificationText
{
    public function render(string $event, Order $order, ?PaymentReceipt $receipt = null): array
    {
        $reference = $order->public_reference;

        return match ($event) {
            'order.created' => ['ثبت سفارش', 'سفارش '.$reference.' ثبت شد و در انتظار پرداخت است.'],
            'payment.receipt_submitted' => ['دریافت رسید', 'رسید سفارش '.$reference.' دریافت شد و در انتظار بررسی است.'],
            'payment.approved' => ['پرداخت تأیید شد', 'پرداخت سفارش '.$reference.' تأیید شد.'],
            'payment.receipt_rejected' => ['رسید رد شد', 'رسید سفارش '.$reference.' رد شد. '.(string) ($receipt?->rejection_reason ?? '')],
            'order.preparing' => ['آماده‌سازی سفارش', 'سفارش '.$reference.' در حال آماده‌سازی است.'],
            'order.shipped' => ['سفارش ارسال شد', 'سفارش '.$reference.' ارسال شد. جزئیات رهگیری را در حساب خود ببینید.'],
            'order.delivered' => ['سفارش تحویل شد', 'سفارش '.$reference.' تحویل شد.'],
            'order.canceled' => ['سفارش لغو شد', 'سفارش '.$reference.' لغو شد.'],
            default => ['به‌روزرسانی سفارش', 'وضعیت سفارش '.$reference.' تغییر کرده است.'],
        };
    }
}
