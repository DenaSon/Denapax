<?php

namespace App\Integrations\Telegram;

use App\Application\Actions\AddressConversation;
use App\Application\Actions\CreateOrder;
use App\Application\Actions\ManageCart;
use App\Application\Actions\PrepareCheckout;
use App\Models\Customer;
use App\Models\Order;
use App\Support\Money;
use App\Support\TelegramHtml;
use Illuminate\Support\Str;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\CopyTextButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton as Button;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup as Keyboard;

class CartCheckoutMessages
{
    public function __construct(
        private readonly ManageCart $carts,
        private readonly PrepareCheckout $checkout,
        private readonly CreateOrder $orders,
        private readonly AddressConversation $conversation,
    ) {}

    public function show(Nutgram $bot, Customer $customer, int $page = 1): void
    {
        $items = $this->carts->cart($customer)->items()->with('variant.product')->orderBy('id')->get();
        if ($items->isEmpty()) {
            $bot->sendMessage(
                text: '🛒 سبد خرید شما خالی است. برای شروع، یک محصول انتخاب کنید.',
                reply_markup: Keyboard::make()->addRow(
                    StorefrontButtons::primary('🌰 مشاهده محصولات', 'menu:catalog'),
                    Button::make('🏠 منوی اصلی', callback_data: 'menu:home'),
                ),
            );

            return;
        }

        $perPage = 9;
        $pages = (int) ceil($items->count() / $perPage);
        $page = min($pages, max(1, $page));
        $subtotal = $items->sum(fn ($item) => ($item->variant?->price_irr ?? 0) * $item->quantity);

        $message = '🛒 '.TelegramHtml::bold('سبد خرید');
        if ($pages > 1) {
            $message .= "\nصفحه ".$page.' از '.$pages;
        }
        $keyboard = Keyboard::make();

        foreach ($items->forPage($page, $perPage) as $index => $item) {
            $number = $index + 1;
            $variant = $item->variant;
            $title = $variant?->product?->title ?? 'محصول غیرفعال';
            $unit = $variant?->price_irr ?? 0;
            $message .= "\n\n".$number.'. '.TelegramHtml::bold(mb_substr($title, 0, 90))
                .' | '.TelegramHtml::escape(mb_substr($variant?->title ?? 'تنوع غیرفعال', 0, 70))
                ."\n".$item->quantity.' × '.$this->toman($unit).' = '
                .$this->toman($unit * $item->quantity).' تومان';

            $keyboard->addRow(
                Button::make('افزایش +۱', callback_data: 'cart:plus:'.$item->product_variant_id),
                Button::make('کاهش -۱', callback_data: 'cart:minus:'.$item->product_variant_id),
            );
            $keyboard->addRow(StorefrontButtons::danger('🗑️ حذف #'.$number, 'cart:remove:'.$item->product_variant_id));
        }

        $message .= "\n\n".TelegramHtml::bold('جمع سبد: '.$this->toman((int) $subtotal).' تومان')
            ."\n🚚 هزینه ارسال در مرحله بعد محاسبه می‌شود.";

        $pager = [];
        if ($page > 1) {
            $pager[] = Button::make('⬅️ قبلی', callback_data: 'cart:page:'.($page - 1));
        }
        if ($page < $pages) {
            $pager[] = Button::make('بعدی ➡️', callback_data: 'cart:page:'.($page + 1));
        }
        if ($pager !== []) {
            $keyboard->addRow(...$pager);
        }

        $keyboard->addRow(StorefrontButtons::success('✅ ادامه و ثبت سفارش', 'checkout:start'));
        $keyboard->addRow(
            StorefrontButtons::primary('🛍️ افزودن محصول دیگر', 'menu:catalog'),
            Button::make('🏠 منوی اصلی', callback_data: 'menu:home'),
        );

        $bot->sendMessage(text: mb_substr($message, 0, 3900), parse_mode: ParseMode::HTML, reply_markup: $keyboard);
    }

    public function chooseAddress(Nutgram $bot, Customer $customer): void
    {
        if ($this->carts->cart($customer)->items()->doesntExist()) {
            $bot->sendMessage(
                text: 'سبد خرید شما خالی است. ابتدا یک محصول انتخاب کنید.',
                reply_markup: Keyboard::make()->addRow(
                    StorefrontButtons::primary('🌰 محصولات', 'menu:catalog'),
                    Button::make('🏠 منوی اصلی', callback_data: 'menu:home'),
                ),
            );

            return;
        }

        $addresses = $customer->addresses()->orderByDesc('is_default')->orderBy('id')->get();
        $keyboard = Keyboard::make();
        foreach ($addresses as $address) {
            $keyboard->addRow(Button::make(
                ($address->is_default ? '⭐ ' : '').$address->recipient_name.'، '.$address->city.' #'.$address->id,
                callback_data: 'checkout:address:'.$address->id,
            ));
        }
        $keyboard->addRow(StorefrontButtons::primary('📍 نشانی جدید', 'addr:new'));
        $keyboard->addRow(
            Button::make('⬅️ سبد خرید', callback_data: 'menu:cart'),
            Button::make('🏠 منوی اصلی', callback_data: 'menu:home'),
        );

        $bot->sendMessage(
            text: $addresses->isEmpty()
                ? '📍 هنوز نشانی تحویل ندارید. ابتدا نشانی گیرنده را ثبت کنید.'
                : '📍 نشانی تحویل سفارش را انتخاب کنید:',
            reply_markup: $keyboard,
        );
    }

    public function preview(Nutgram $bot, Customer $customer, int $addressId): void
    {
        $quote = $this->checkout->execute($customer, $addressId);
        $key = (string) Str::uuid();
        $session = $this->conversation->session($customer);
        $session->update([
            'state' => 'checkout:confirm',
            'context' => [
                'checkout_key' => $key,
                'address_id' => $addressId,
                'fingerprint' => $this->checkout->fingerprint($quote),
            ],
            'expires_at' => now()->addMinutes(20),
            'updated_at' => now(),
        ]);

        $text = TelegramHtml::bold('🧾 پیش‌نمایش نهایی سفارش')."\n";
        foreach ($quote['items'] as $line) {
            $text .= "\n• ".TelegramHtml::escape($line['product_title_snapshot']).' / '.TelegramHtml::escape($line['variant_title_snapshot'])
                .' × '.$line['quantity'].' : '.$this->toman($line['line_total_irr']).' تومان';
        }
        $text .= "\n\n".TelegramHtml::bold('👤 گیرنده: ').TelegramHtml::escape($quote['address']['recipient_name'])
            ."\nتلفن گیرنده: ".TelegramHtml::escape($quote['address']['recipient_mobile']);
        $text .= "\n📍 نشانی: ".TelegramHtml::escape($quote['address']['province']).'، '.TelegramHtml::escape($quote['address']['city']).'، '.TelegramHtml::escape($quote['address']['address_line'])
            ."\nکد پستی: ".TelegramHtml::escape($quote['address']['postal_code']);
        $text .= "\n\nوزن مرسوله: ".$quote['shipping_weight_grams'].' گرم';
        $text .= "\n🛒 جمع کالا: ".$this->toman($quote['goods_subtotal_irr']).' تومان';
        $text .= "\n🚚 هزینه ارسال: ".$this->toman($quote['shipping_fee_irr']).' تومان';
        $text .= "\n".TelegramHtml::bold('💳 مبلغ قابل پرداخت: '.$this->toman($quote['total_irr']).' تومان');
        $text .= "\n\n".'با تأیید، سفارش ثبت می‌شود و اطلاعات کارت‌به‌کارت نمایش داده خواهد شد.';

        $keyboard = Keyboard::make()
            ->addRow(StorefrontButtons::success('✅ تأیید و ثبت سفارش', 'checkout:confirm:'.$key))
            ->addRow(Button::make('📍 تغییر نشانی', callback_data: 'checkout:start'))
            ->addRow(
                Button::make('⬅️ سبد خرید', callback_data: 'menu:cart'),
                Button::make('🏠 منوی اصلی', callback_data: 'menu:home'),
            );

        $bot->sendMessage(text: mb_substr($text, 0, 3950), parse_mode: ParseMode::HTML, reply_markup: $keyboard);
    }

    public function confirm(Nutgram $bot, Customer $customer, string $key): void
    {
        $existing = $customer->orders()->where('checkout_key', $key)->first();
        if ($existing) {
            $this->orderDetails($bot, $existing);

            return;
        }

        $session = $this->conversation->session($customer);
        $context = $session->context ?? [];
        $invalidState = $session->state !== 'checkout:confirm';
        $expired = $session->expires_at?->isPast() ?? true;
        $wrongKey = ($context['checkout_key'] ?? '') !== $key;
        if ($invalidState || $expired || $wrongKey) {
            $bot->sendMessage(
                text: 'مهلت پیش‌نمایش سفارش تمام شده یا سبد تغییر کرده است. مبلغ و نشانی را دوباره بررسی کنید.',
                reply_markup: Keyboard::make()->addRow(
                    StorefrontButtons::primary('🛒 بررسی دوباره', 'checkout:start'),
                    Button::make('🏠 منوی اصلی', callback_data: 'menu:home'),
                ),
            );

            return;
        }

        $order = $this->orders->execute(
            $customer,
            (int) $context['address_id'],
            $key,
            (string) $context['fingerprint'],
        );
        $this->conversation->reset($customer);
        $this->orderDetails($bot, $order);
    }

    private function orderDetails(Nutgram $bot, Order $order): void
    {
        $keyboard = Keyboard::make();

        if ($order->order_status === 'awaiting_payment') {
            $payment = $order->payment_instructions_snapshot;
            $text = TelegramHtml::bold('✅ سفارش شما ثبت شد!')
                ."\n".TelegramHtml::bold('شماره سفارش: ').TelegramHtml::escape($order->public_reference)
                ."\n".TelegramHtml::bold('وضعیت: ⏳ در انتظار پرداخت')
                ."\nوضعیت رسید: ".TelegramHtml::escape(OrderStateLabels::payment($order->payment_status))
                ."\n\n🛒 جمع کالا: ".$this->toman($order->goods_subtotal_irr).' تومان'
                ."\n🚚 هزینه ارسال: ".$this->toman($order->shipping_fee_irr).' تومان'
                ."\n".TelegramHtml::bold('💳 مبلغ قابل پرداخت: '.$this->toman($order->total_irr).' تومان');

            if (in_array($order->payment_status, ['awaiting_receipt', 'rejected'], true)) {
                $text .= "\n\n".TelegramHtml::bold('💳 اطلاعات واریز')
                    ."\n".'شماره کارت: <code>'.TelegramHtml::escape((string) ($payment['card_number'] ?? '')).'</code>'
                    ."\nبه نام: ".TelegramHtml::escape((string) ($payment['card_holder_name'] ?? ''))
                    ."\n\nپس از انتقال وجه، رسید این سفارش را ارسال کنید."
                    ."\n\n".TelegramHtml::quote('پس از بررسی و تأیید پرداخت، سفارش شما نهایی شده و در صف آماده‌سازی قرار می‌گیرد.');
                if (is_string($payment['card_number'] ?? null) && $payment['card_number'] !== '') {
                    $keyboard->addRow(Button::make(
                        '📋 کپی شماره کارت',
                        copy_text: CopyTextButton::make($payment['card_number']),
                    ));
                }
                $keyboard->addRow(StorefrontButtons::primary('🧾 ارسال رسید پرداخت', 'receipt:order:'.$order->id));
            } else {
                $text .= "\n\n".TelegramHtml::quote('رسید دریافت شده است؛ پس از بررسی و تأیید پرداخت، نتیجه اعلام می‌شود.');
            }
        } else {
            // Replayed checkout callbacks must not ask a paid/canceled user to transfer again.
            $text = TelegramHtml::bold('📦 سفارش '.(string) $order->public_reference)
                ."\nوضعیت سفارش: ".TelegramHtml::escape(OrderStateLabels::order($order->order_status))
                ."\nوضعیت پرداخت: ".TelegramHtml::escape(OrderStateLabels::payment($order->payment_status))
                ."\n".TelegramHtml::bold('مبلغ سفارش: '.$this->toman($order->total_irr).' تومان');
        }

        $keyboard->addRow(Button::make('📦 مشاهده وضعیت سفارش', callback_data: 'myorder:'.$order->id));
        $keyboard->addRow(Button::make('🏠 منوی اصلی', callback_data: 'menu:home'));

        $bot->sendMessage(text: $text, parse_mode: ParseMode::HTML, reply_markup: $keyboard);
    }

    private function toman(int $irr): string
    {
        return Money::formatToman($irr);
    }
}
