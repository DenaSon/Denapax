<?php

namespace App\Application\Notifications;

use App\Support\Money;
use App\Models\Order;
use App\Models\PaymentReceipt;
use App\Support\TelegramHtml;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton as Button;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup as Keyboard;

class AdminGroupAlertText
{
    public const EVENTS = ['order.created', 'payment.receipt_submitted', 'payment.approved'];

    public function render(string $event, Order $order): string
    {
        $heading = match ($event) {
            'order.created' => '🛒 سفارش جدید ثبت شد',
            'payment.receipt_submitted' => '🧾 رسید جدید برای بررسی',
            'payment.approved' => '✅ پرداخت سفارش تأیید شد',
            default => throw new \InvalidArgumentException('Unknown admin group alert event'),
        };
        $status = match ($event) {
            'order.created' => '⏳ در انتظار پرداخت مشتری',
            'payment.receipt_submitted' => '⏳ در انتظار بررسی دستی رسید',
            'payment.approved' => '✅ تأیید پرداخت توسط فروشگاه',
        };

        return TelegramHtml::bold($heading)
            ."\nشماره سفارش: <code>".TelegramHtml::escape($order->public_reference).'</code>'
            ."\n".TelegramHtml::bold('مبلغ سفارش: '.Money::formatToman($order->total_irr).' تومان')
            ."\nوضعیت: ".$status;
    }

    public function keyboard(string $event, Order $order, ?PaymentReceipt $receipt): Keyboard
    {
        if ($event === 'payment.receipt_submitted') {
            if (! $receipt || $receipt->order_id !== $order->id) {
                throw new \InvalidArgumentException('Receipt does not match the order');
            }

            $url = route('admin.payments', ['receipt' => $receipt->id]);

            return Keyboard::make()->addRow(Button::make('🔎 بررسی همین رسید در پنل', url: $url));
        }

        return Keyboard::make()->addRow(Button::make(
            '📦 مشاهده سفارش در پنل',
            url: route('admin.orders.show', ['order' => $order->id]),
        ));
    }
}
