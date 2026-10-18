<?php

namespace App\Integrations\Telegram;

use App\Models\Customer;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton as Button;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup as Keyboard;

class CustomerOrdersBot
{
    public function list(Nutgram $bot, Customer $customer, int $page = 1): void
    {
        $orders = $customer->orders()
            ->latest()
            ->paginate(perPage: 5, page: min(max(1, $page), 500));

        if ($orders->isEmpty()) {
            $bot->sendMessage(
                text: 'سفارشی در این صفحه پیدا نشد.',
                reply_markup: Keyboard::make()->addRow(
                    Button::make('🌰 مشاهده محصولات', callback_data: 'menu:catalog'),
                    Button::make('🏠 منوی اصلی', callback_data: 'menu:home'),
                ),
            );

            return;
        }

        $keyboard = Keyboard::make();
        foreach ($orders as $order) {
            $keyboard->addRow(Button::make(
                $order->order_number.' — '.$this->label($order->order_status),
                callback_data: 'myorder:'.$order->id,
            ));
        }
        $nav = [];
        if ($orders->currentPage() > 1) {
            $nav[] = Button::make('صفحه قبل', callback_data: 'myorders:'.($orders->currentPage() - 1));
        }
        if ($orders->hasMorePages()) {
            $nav[] = Button::make('صفحه بعد', callback_data: 'myorders:'.($orders->currentPage() + 1));
        }
        if ($nav) {
            $keyboard->addRow(...$nav);
        }

        $keyboard->addRow(Button::make('🏠 منوی اصلی', callback_data: 'menu:home'));

        $bot->sendMessage(text: '📦 سفارش‌های من', reply_markup: $keyboard);
    }

    public function details(Nutgram $bot, Customer $customer, int $orderId): void
    {
        $order = $customer->orders()->with(['items', 'shipment'])->find($orderId);
        if (! $order) {
            $bot->sendMessage('سفارش در حساب شما پیدا نشد.');

            return;
        }

        $text = '📦 شماره سفارش: '.$order->order_number
            ."\nوضعیت: ".$this->label($order->order_status)
            ."\nپرداخت: ".$this->paymentLabel($order->payment_status)
            ."\nمبلغ کل: ".number_format(intdiv($order->total_irr, 10)).' تومان';

        foreach ($order->items as $item) {
            $text .= "\n• ".$item->product_title_snapshot.' / '.$item->variant_title_snapshot
                .' × '.$item->quantity;
        }

        if ($order->shipment) {
            $text .= "\nشرکت حمل: ".$order->shipment->carrier
                ."\nکد رهگیری: ".($order->shipment->tracking_code ?: 'ثبت نشده');
        }

        $bot->sendMessage(
            text: mb_substr($text, 0, 3900),
            reply_markup: Keyboard::make()->addRow(
                Button::make('⬅️ سفارش‌های من', callback_data: 'myorders:1'),
                Button::make('🏠 منوی اصلی', callback_data: 'menu:home'),
            ),
        );
    }

    private function label(string $state): string
    {
        return match ($state) {
            'awaiting_payment' => 'در انتظار پرداخت',
            'confirmed' => 'تأییدشده',
            'preparing' => 'در حال آماده‌سازی',
            'shipped' => 'ارسال‌شده',
            'delivered' => 'تحویل‌شده',
            'canceled' => 'لغوشده',
            default => 'نامشخص',
        };
    }

    private function paymentLabel(string $state): string
    {
        return match ($state) {
            'awaiting_receipt' => 'در انتظار رسید',
            'under_review' => 'در حال بررسی رسید',
            'rejected' => 'نیازمند ارسال مجدد رسید',
            'approved' => 'تأییدشده',
            default => 'نامشخص',
        };
    }
}
