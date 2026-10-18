<?php

namespace App\Integrations\Telegram;

use App\Application\Actions\AddressConversation;
use App\Application\Actions\SubmitPaymentReceipt;
use App\Models\Customer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton as Button;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup as Keyboard;

class ReceiptBot
{
    private const MAX_FILE_BYTES = 8 * 1024 * 1024;

    private const ACCEPTED_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(
        private readonly AddressConversation $conversation,
        private readonly SubmitPaymentReceipt $submit,
    ) {}

    public function orders(Nutgram $bot, Customer $customer): void
    {
        $orders = $customer->orders()
            ->where('order_status', 'awaiting_payment')
            ->latest()
            ->limit(10)
            ->get();

        if ($orders->isEmpty()) {
            $bot->sendMessage(
                text: 'سفارش پرداخت‌نشده‌ای برای ارسال رسید ندارید.',
                reply_markup: Keyboard::make()->addRow(
                    Button::make('📦 سفارش‌های من', callback_data: 'menu:orders'),
                    Button::make('🏠 منوی اصلی', callback_data: 'menu:home'),
                ),
            );

            return;
        }

        $keyboard = Keyboard::make();
        foreach ($orders as $order) {
            $label = $order->order_number.' — '.$this->paymentLabel($order->payment_status);
            $keyboard->addRow(Button::make($label, callback_data: 'receipt:order:'.$order->id));
        }

        $keyboard->addRow(Button::make('🏠 منوی اصلی', callback_data: 'menu:home'));

        $bot->sendMessage(
            text: '🧾 برای مشاهده وضعیت و ارسال رسید، سفارش خود را انتخاب کنید.',
            reply_markup: $keyboard,
        );
    }

    public function order(Nutgram $bot, Customer $customer, int $orderId): void
    {
        $order = $customer->orders()->find($orderId);

        if (! $order) {
            $bot->sendMessage('سفارش متعلق به شما نیست.');

            return;
        }

        $latest = $order->receipts()->latest('attempt_number')->first();
        $text = '🧾 سفارش '.$order->order_number."\nوضعیت پرداخت: ".$this->paymentLabel($order->payment_status);
        $text .= "\nمبلغ: ".number_format(intdiv($order->total_irr, 10)).' تومان';

        if ($latest?->status === 'rejected') {
            $text .= "\nدلیل رد رسید قبلی: ".$latest->rejection_reason;
        }

        $keyboard = Keyboard::make();
        if ($order->order_status === 'awaiting_payment'
            && in_array($order->payment_status, ['awaiting_receipt', 'rejected'], true)) {
            $payment = $order->payment_instructions_snapshot;
            $text .= "\nشماره کارت: ".$payment['card_number']."\nبه نام: ".$payment['card_holder_name'];
            $text .= "\nفقط پس از انتقال وجه، عکس یا فایل تصویری رسید را ارسال کنید.";
            $keyboard->addRow(Button::make('📎 ارسال رسید', callback_data: 'receipt:upload:'.$order->id));
        }

        $keyboard->addRow(
            Button::make('⬅️ سفارش‌های پرداخت‌نشده', callback_data: 'menu:receipt'),
            Button::make('🏠 منوی اصلی', callback_data: 'menu:home'),
        );
        $bot->sendMessage(text: mb_substr($text, 0, 3900), reply_markup: $keyboard);
    }

    public function start(Nutgram $bot, Customer $customer, int $orderId): void
    {
        $order = $customer->orders()->find($orderId);

        if (! $order || $order->order_status !== 'awaiting_payment'
            || ! in_array($order->payment_status, ['awaiting_receipt', 'rejected'], true)) {
            $bot->sendMessage('این سفارش در حال حاضر امکان دریافت رسید ندارد.');

            return;
        }

        $this->conversation->session($customer)->update([
            'state' => 'receipt:upload',
            'context' => ['order_id' => $order->id],
            'expires_at' => now()->addMinutes(30),
            'updated_at' => now(),
        ]);

        $bot->sendMessage('📷 تصویر رسید را به‌صورت عکس یا فایل JPG/PNG/WebP بفرستید (حداکثر ۸ مگابایت). /cancel برای لغو.');
    }

    public function handle(Nutgram $bot, Customer $customer): void
    {
        $session = $this->conversation->session($customer);
        if ($session->state !== 'receipt:upload' || ! $session->expires_at || $session->expires_at->isPast()) {
            $this->conversation->reset($customer);
            $bot->sendMessage('مهلت ارسال رسید تمام شده؛ دوباره سفارش را انتخاب کنید.');

            return;
        }

        $message = $bot->message();
        $photo = collect($message?->photo ?? [])->last();
        $document = $message?->document;
        $fileId = null;
        $mime = null;
        $size = null;

        if ($photo) {
            $fileId = $photo->file_id;
            $mime = 'image/jpeg';
            $size = $photo->file_size ?? null;
        } elseif ($document) {
            $fileId = $document->file_id;
            $mime = $document->mime_type ?? null;
            $size = $document->file_size ?? null;
        }

        if (! $fileId || ! isset(self::ACCEPTED_TYPES[$mime])
            || ($size !== null && ($size < 1 || $size > self::MAX_FILE_BYTES))) {
            $bot->sendMessage('فقط تصویر JPG، PNG یا WebP با حداکثر حجم ۸ مگابایت پذیرفته می‌شود.');

            return;
        }

        $path = 'payment-receipts/'.Str::uuid().'.'.self::ACCEPTED_TYPES[$mime];
        $disk = Storage::disk('receipts');

        try {
            $file = $bot->getFile($fileId);
            if (! $file || ! $bot->downloadFileToDisk($file, $path, 'receipts')) {
                throw ValidationException::withMessages(['receipt' => 'دریافت فایل از تلگرام ممکن نشد.']);
            }

            $actualSize = $disk->size($path);
            $actualMime = mime_content_type($disk->path($path));
            if ($actualSize < 1 || $actualSize > self::MAX_FILE_BYTES
                || $actualMime !== $mime
                || getimagesize($disk->path($path)) === false) {
                throw ValidationException::withMessages(['receipt' => 'فایل دریافتی تصویر معتبر یا مجاز نیست.']);
            }

            $this->submit->execute($customer, (int) $session->context['order_id'], $path, $fileId);
            $this->conversation->reset($customer);
            $bot->sendMessage('✅ رسید شما ثبت شد و در انتظار بررسی دستی واریز توسط فروشگاه است. این پیام به‌معنای تأیید پرداخت نیست.');
        } catch (ValidationException $exception) {
            $disk->delete($path);
            $bot->sendMessage(collect($exception->errors())->flatten()->first() ?? 'رسید پذیرفته نشد.');
        } catch (\Throwable $exception) {
            $disk->delete($path);
            // Do not log provider URLs: they can contain the Telegram bot token.
            $bot->sendMessage('دریافت یا ذخیره رسید موقتاً ناموفق بود؛ دوباره تلاش کنید.');
        }
    }

    private function paymentLabel(string $status): string
    {
        return match ($status) {
            'awaiting_receipt' => 'در انتظار رسید',
            'under_review' => 'در حال بررسی',
            'rejected' => 'ردشده، نیاز به ارسال مجدد',
            'approved' => 'تأییدشده',
            default => 'نامشخص',
        };
    }
}
