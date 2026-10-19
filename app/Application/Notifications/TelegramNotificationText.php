<?php

namespace App\Application\Notifications;

use App\Support\Money;
use App\Models\Order;
use App\Models\PaymentReceipt;
use App\Support\TelegramHtml;

class TelegramNotificationText
{
    public function render(string $event, Order $order, ?PaymentReceipt $receipt = null): string
    {
        $reference = TelegramHtml::escape($order->public_reference);

        return match ($event) {
            'order.created' => TelegramHtml::bold('🌰 سفارش ثبت شد')
                ."\nشماره سفارش: ".$reference
                ."\n".TelegramHtml::bold('وضعیت: در انتظار پرداخت')
                ."\n\n🛒 جمع کالا: ".$this->toman($order->goods_subtotal_irr)
                ."\n🚚 هزینه ارسال: ".$this->toman($order->shipping_fee_irr)
                ."\n".TelegramHtml::bold('💳 مبلغ قابل پرداخت: '.$this->toman($order->total_irr))
                ."\n\n".TelegramHtml::bold('اطلاعات واریز')
                ."\nشماره کارت: ".TelegramHtml::escape((string) ($order->payment_instructions_snapshot['card_number'] ?? ''))
                ."\nبه نام: ".TelegramHtml::escape((string) ($order->payment_instructions_snapshot['card_holder_name'] ?? ''))
                ."\n\nپس از واریز، از بخش «🧾 ارسال رسید» ابتدا همین سفارش را انتخاب و رسید را ارسال کنید."
                ."\n\n".TelegramHtml::quote('پس از بررسی و تأیید پرداخت، سفارش شما نهایی شده و در صف آماده‌سازی قرار می‌گیرد.'),
            'payment.receipt_submitted' => TelegramHtml::bold('🧾 رسید سفارش دریافت شد')
                ."\nشماره سفارش: ".$reference
                ."\n".TelegramHtml::bold('وضعیت پرداخت: در انتظار بررسی فروشگاه')
                ."\n".TelegramHtml::quote('پس از بررسی و تأیید پرداخت، نتیجه به شما اطلاع داده می‌شود.'),
            'payment.receipt_rejected' => TelegramHtml::bold('❌ رسید سفارش تأیید نشد')
                ."\nشماره سفارش: ".$reference
                ."\n".TelegramHtml::bold('دلیل: ').TelegramHtml::escape($receipt?->rejection_reason ?: 'واریز تأیید نشده است.')
                ."\nاز بخش «🧾 ارسال رسید»، همین سفارش را انتخاب و رسید جدید ارسال کنید.",
            'payment.approved' => TelegramHtml::bold('✅ پرداخت سفارش تأیید شد')
                ."\nشماره سفارش: ".$reference
                ."\nوضعیت سفارش: تأییدشده؛ در صف آماده‌سازی"
                ."\nبا شروع آماده‌سازی، جداگانه اطلاع می‌دهیم.",
            'order.preparing' => TelegramHtml::bold('📦 آماده‌سازی سفارش آغاز شد')
                ."\nشماره سفارش: ".$reference
                ."\nوضعیت سفارش: در حال آماده‌سازی",
            'order.shipped' => TelegramHtml::bold('🚚 سفارش ارسال شد')
                ."\nشماره سفارش: ".$reference
                ."\nشرکت حمل: ".TelegramHtml::escape($order->shipment?->carrier ?: 'ثبت نشده')
                ."\nکد رهگیری: ".TelegramHtml::escape($order->shipment?->tracking_code ?: 'ثبت نشده')
                ."\nجزئیات را در «📦 سفارش‌های من» ببینید.",
            'order.delivered' => TelegramHtml::bold('✅ تحویل سفارش ثبت شد')
                ."\nشماره سفارش: ".$reference
                ."\nاز همراهی شما با دنا سپاسگزاریم. 🌿",
            'order.canceled' => TelegramHtml::bold('❌ سفارش لغو شد')
                ."\nشماره سفارش: ".$reference
                ."\nوضعیت سفارش را در «📦 سفارش‌های من» ببینید.",
            default => throw new \InvalidArgumentException('Unknown notification event'),
        };
    }

    private function toman(int $irr): string
    {
        return Money::formatToman($irr).' تومان';
    }
}
