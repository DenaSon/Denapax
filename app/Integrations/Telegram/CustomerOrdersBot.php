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
                    StorefrontButtons::primary('🌰 مشاهده محصولات', 'menu:catalog'),
                    Button::make('🏠 منوی اصلی', callback_data: 'menu:home'),
                ),
            );

            return;
        }

        $keyboard = Keyboard::make();
        foreach ($orders as $order) {
            $keyboard->addRow(Button::make(
                $order->order_number.' · '.OrderStateLabels::order($order->order_status),
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
            $bot->sendMessage(
                text: 'این سفارش در حساب شما پیدا نشد.',
                reply_markup: Keyboard::make()->addRow(
                    Button::make('⬅️ سفارش‌های من', callback_data: 'menu:orders'),
                    Button::make('🏠 منوی اصلی', callback_data: 'menu:home'),
                ),
            );

            return;
        }

        $text = '📦 شماره سفارش: '.$order->order_number
            ."\nوضعیت سفارش: ".OrderStateLabels::order($order->order_status)
            ."\nوضعیت پرداخت: ".OrderStateLabels::payment($order->payment_status)
            ."\n\n🛒 جمع کالا: ".number_format(intdiv($order->goods_subtotal_irr, 10)).' تومان'
            ."\n🚚 هزینه ارسال: ".number_format(intdiv($order->shipping_fee_irr, 10)).' تومان'
            ."\n💳 مبلغ کل: ".number_format(intdiv($order->total_irr, 10)).' تومان';

        foreach ($order->items as $item) {
            $text .= "\n• ".$item->product_title_snapshot.' / '.$item->variant_title_snapshot
                .' × '.$item->quantity;
        }

        if ($order->shipment) {
            $text .= "\n\n🚚 شرکت حمل: ".$order->shipment->carrier
                ."\nکد رهگیری: ".($order->shipment->tracking_code ?: 'هنوز ثبت نشده');
        }

        $keyboard = Keyboard::make();
        if ($order->order_status === 'awaiting_payment') {
            if (in_array($order->payment_status, ['awaiting_receipt', 'rejected'], true)) {
                $keyboard->addRow(StorefrontButtons::primary('🧾 ارسال رسید پرداخت', 'receipt:order:'.$order->id));
            } elseif ($order->payment_status === 'under_review') {
                $text .= "\n\nرسید در حال بررسی است. تا اعلام نتیجه، نیازی به پرداخت یا ارسال دوباره نیست.";
                $keyboard->addRow(Button::make('🧾 وضعیت رسید', callback_data: 'receipt:order:'.$order->id));
            }
        }

        $keyboard->addRow(
            Button::make('⬅️ سفارش‌های من', callback_data: 'myorders:1'),
            Button::make('🏠 منوی اصلی', callback_data: 'menu:home'),
        );

        $bot->sendMessage(text: mb_substr($text, 0, 3900), reply_markup: $keyboard);
    }
}
