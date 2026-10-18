<?php

namespace App\Integrations\Telegram;

use App\Application\Actions\AddressConversation;
use App\Application\Actions\CreateOrder;
use App\Application\Actions\ManageCart;
use App\Application\Actions\PrepareCheckout;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Str;
use SergiX44\Nutgram\Nutgram;
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
        $items = $this->carts->cart($customer)->items()->with('variant.product')->get();
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

        $perPage = 5;
        $pages = (int) ceil($items->count() / $perPage);
        $page = min($pages, max(1, $page));
        $subtotal = $items->sum(fn ($item) => ($item->variant?->price_irr ?? 0) * $item->quantity);

        $message = '🛒 سبد خرید شما'."\nصفحه ".$page.' از '.$pages;
        $keyboard = Keyboard::make();

        foreach ($items->forPage($page, $perPage) as $index => $item) {
            $number = $index + 1;
            $variant = $item->variant;
            $title = $variant?->product?->title ?? 'محصول غیرفعال';
            $unit = $variant?->price_irr ?? 0;
            $message .= "\n\n".$number.'. '.mb_substr($title, 0, 90).' / '.mb_substr($variant?->title ?? 'تنوع غیرفعال', 0, 70)
                ."\n".$this->toman($unit).' تومان × '.$item->quantity.' = '
                .$this->toman($unit * $item->quantity).' تومان';

            $keyboard->addRow(
                Button::make('➕ #'.$number, callback_data: 'cart:plus:'.$item->product_variant_id),
                Button::make('➖ #'.$number, callback_data: 'cart:minus:'.$item->product_variant_id),
            );
            $keyboard->addRow(StorefrontButtons::danger('🗑️ حذف #'.$number, 'cart:remove:'.$item->product_variant_id));
        }

        $message .= "\n\nتعداد اقلام: ".$items->sum('quantity')
            ."\nجمع کالا: ".$this->toman((int) $subtotal).' تومان'
            ."\nهزینه ارسال در مرحله بعد محاسبه می‌شود.";

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
            StorefrontButtons::primary('🌰 ادامه خرید', 'menu:catalog'),
            Button::make('🏠 منوی اصلی', callback_data: 'menu:home'),
        );

        $bot->sendMessage(text: mb_substr($message, 0, 3900), reply_markup: $keyboard);
    }

    public function chooseAddress(Nutgram $bot, Customer $customer): void
    {
        if ($this->carts->cart($customer)->items()->doesntExist()) {
            $bot->sendMessage('سبد خرید خالی است.');

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
        $keyboard->addRow(Button::make('ثبت نشانی جدید', callback_data: 'addr:new'));
        $keyboard->addRow(Button::make('بازگشت به سبد', callback_data: 'menu:cart'));

        $bot->sendMessage(
            text: $addresses->isEmpty()
                ? 'ابتدا نشانی گیرنده را ثبت کنید و سپس از سبد خرید سفارش را بررسی کنید.'
                : 'نشانی دریافت سفارش را انتخاب کنید:',
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

        $text = "🧾 پیش‌نمایش نهایی سفارش\n";
        foreach ($quote['items'] as $line) {
            $text .= "\n• ".$line['product_title_snapshot'].' / '.$line['variant_title_snapshot']
                .' × '.$line['quantity'].' : '.$this->toman($line['line_total_irr']).' تومان';
        }
        $text .= "\n\nگیرنده: ".$quote['address']['recipient_name'];
        $text .= "\nنشانی: ".$quote['address']['province'].'، '.$quote['address']['city'].'، '.$quote['address']['address_line'];
        $text .= "\nوزن مرسوله: ".$quote['shipping_weight_grams'].' گرم';
        $text .= "\nمجموع کالا: ".$this->toman($quote['goods_subtotal_irr']).' تومان';
        $text .= "\nهزینه ارسال: ".$this->toman($quote['shipping_fee_irr']).' تومان';
        $text .= "\nمبلغ نهایی: ".$this->toman($quote['total_irr']).' تومان';
        $text .= "\n\n".'با تأیید، سفارش ثبت می‌شود و اطلاعات کارت‌به‌کارت نمایش داده خواهد شد.';

        $keyboard = Keyboard::make()
            ->addRow(StorefrontButtons::success('✅ تأیید و ثبت سفارش', 'checkout:confirm:'.$key))
            ->addRow(Button::make('تغییر نشانی', callback_data: 'checkout:start'))
            ->addRow(Button::make('بازگشت به سبد', callback_data: 'menu:cart'));

        $bot->sendMessage(text: mb_substr($text, 0, 3950), reply_markup: $keyboard);
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
            $bot->sendMessage('پیش‌نمایش سفارش منقضی شده یا تغییر کرده است؛ دوباره از سبد خرید بررسی کنید.');

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
        $payment = $order->payment_instructions_snapshot;
        $bot->sendMessage(
            "✅ سفارش ثبت شد\nشماره: ".$order->order_number
            ."\nمبلغ: ".$this->toman($order->total_irr).' تومان'
            ."\n\nپرداخت کارت‌به‌کارت:"
            ."\nشماره کارت: ".$payment['card_number']
            ."\nبه نام: ".$payment['card_holder_name']
            ."\n\nپس از انتقال وجه، رسید را از دکمه زیر بارگذاری کنید.",
            reply_markup: Keyboard::make()->addRow(
                StorefrontButtons::primary('🧾 ارسال رسید سفارش', 'receipt:order:'.$order->id),
            ),
        );
    }

    private function toman(int $irr): string
    {
        return $irr % 10 === 0
            ? number_format(intdiv($irr, 10))
            : number_format($irr / 10, 1);
    }
}
