<?php

namespace App\Application\Notifications;

use App\Models\Order;
use App\Models\PaymentReceipt;

class TelegramNotificationText
{
    public function render(string $event, Order $order, ?PaymentReceipt $receipt = null): string
    {
        $reference = $order->order_number;

        return match ($event) {
            'order.created' => '🌰 سفارش '.$reference.' ثبت شد.'
                ."\nوضعیت: در انتظار پرداخت"
                ."\n🛒 جمع کالا: ".$this->toman($order->goods_subtotal_irr)
                ."\n🚚 هزینه ارسال: ".$this->toman($order->shipping_fee_irr)
                ."\n💳 مبلغ قابل پرداخت: ".$this->toman($order->total_irr)
                ."\n\nشماره کارت: ".$order->payment_instructions_snapshot['card_number']
                ."\nبه نام: ".$order->payment_instructions_snapshot['card_holder_name']
                ."\n\nپس از کارت‌به‌کارت، از بخش «🧾 ارسال رسید» ابتدا همین سفارش را انتخاب و رسید را بارگذاری کنید."
                ."\nارسال رسید به‌معنای تأیید پرداخت نیست.",
            'payment.receipt_submitted' => '🧾 رسید سفارش '.$reference.' دریافت شد.'
                ."\nوضعیت پرداخت: در انتظار بررسی فروشگاه"
                ."\nتا تأیید پرداخت، سفارش در مرحله آماده‌سازی قرار نمی‌گیرد.",
            'payment.receipt_rejected' => '❌ رسید سفارش '.$reference.' تأیید نشد.'
                ."\nدلیل: ".($receipt?->rejection_reason ?: 'واریز تأیید نشده است.')
                ."\nاز بخش «🧾 ارسال رسید»، همین سفارش را انتخاب و رسید جدید ارسال کنید.",
            'payment.approved' => '✅ پرداخت سفارش '.$reference.' تأیید شد.'
                ."\nوضعیت سفارش: تأییدشده؛ در صف آماده‌سازی"
                ."\nبا شروع آماده‌سازی، جداگانه اطلاع می‌دهیم.",
            'order.preparing' => '📦 آماده‌سازی سفارش '.$reference.' آغاز شد.'
                ."\nوضعیت سفارش: در حال آماده‌سازی",
            'order.shipped' => '🚚 سفارش '.$reference.' ارسال شد.'
                ."\nشرکت حمل: ".($order->shipment?->carrier ?: 'ثبت نشده')
                ."\nکد رهگیری: ".($order->shipment?->tracking_code ?: 'ثبت نشده')
                ."\nجزئیات را در «📦 سفارش‌های من» ببینید.",
            'order.delivered' => '✅ تحویل سفارش '.$reference.' ثبت شد.'
                ."\nاز همراهی شما با دنا سپاسگزاریم. 🌿",
            'order.canceled' => '❌ سفارش '.$reference.' لغو شد.'
                ."\nوضعیت سفارش را در «📦 سفارش‌های من» ببینید.",
            default => throw new \InvalidArgumentException('Unknown notification event'),
        };
    }

    private function toman(int $irr): string
    {
        return $irr % 10 === 0
            ? number_format(intdiv($irr, 10)).' تومان'
            : number_format($irr / 10, 1).' تومان';
    }
}
