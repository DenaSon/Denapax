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
            'order.created' => '🌰 سفارش شما ثبت شد: '.$reference
                ."\nمجموع کالا: ".$this->toman($order->goods_subtotal_irr)
                ."\nهزینه ارسال: ".$this->toman($order->shipping_fee_irr)
                ."\nمبلغ کل: ".$this->toman($order->total_irr)
                ."\nشماره کارت: ".$order->payment_instructions_snapshot['card_number']
                ."\nصاحب کارت: ".$order->payment_instructions_snapshot['card_holder_name']
                ."\nلطفاً پس از واریز، رسید را از بخش ارسال رسید بارگذاری کنید.",
            'payment.receipt_submitted' => '🧾 رسید سفارش '.$reference.' دریافت شد و منتظر بررسی بانکی است؛ پرداخت هنوز تأیید نشده است.',
            'payment.receipt_rejected' => '❌ رسید سفارش '.$reference.' رد شد.'
                ."\nدلیل: ".($receipt?->rejection_reason ?: 'عدم تأیید واریز')
                ."\nاز منوی «ارسال رسید» یک تصویر جدید ثبت کنید.",
            'payment.approved' => '✅ پرداخت سفارش '.$reference.' پس از بررسی تأیید شد. سفارش برای آماده‌سازی ثبت شده است.',
            'order.preparing' => '📦 سفارش '.$reference.' در حال آماده‌سازی است.',
            'order.shipped' => '🚚 سفارش '.$reference.' ارسال شد.'
                ."\nشرکت حمل: ".($order->shipment?->carrier ?: 'پست')
                ."\nکد رهگیری: ".($order->shipment?->tracking_code ?: 'در دسترس نیست'),
            'order.delivered' => '✅ تحویل سفارش '.$reference.' ثبت شد. از خریدتان سپاسگزاریم.',
            'order.canceled' => 'سفارش '.$reference.' لغو شد.',
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
