<?php

namespace App\Integrations\Telegram;

use App\Application\Actions\AddressConversation;
use App\Application\Actions\SubmitPaymentReceipt;
use App\Models\Customer;
use App\Support\Money;
use App\Support\TelegramHtml;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\CopyTextButton;
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
            $label = $order->public_reference.' · '.Money::formatToman($order->total_irr).' تومان · '
                .OrderStateLabels::payment($order->payment_status);
            $keyboard->addRow(Button::make($label, callback_data: 'receipt:order:'.$order->id));
        }

        $keyboard->addRow(Button::make('🏠 منوی اصلی', callback_data: 'menu:home'));

        $bot->sendMessage(
            text: '🧾 ابتدا سفارش موردنظر را انتخاب کنید. ارسال رسید فقط برای همان سفارش ثبت می‌شود.',
            reply_markup: $keyboard,
        );
    }

    public function order(Nutgram $bot, Customer $customer, int $orderId): void
    {
        $order = $customer->orders()->find($orderId);

        if (! $order) {
            $bot->sendMessage(
                text: 'این سفارش در حساب شما پیدا نشد.',
                reply_markup: Keyboard::make()->addRow(
                    Button::make('⬅️ انتخاب سفارش', callback_data: 'menu:receipt'),
                    Button::make('🏠 منوی اصلی', callback_data: 'menu:home'),
                ),
            );

            return;
        }

        $latest = $order->receipts()->latest('attempt_number')->first();
        $text = TelegramHtml::bold('🧾 سفارش '.$order->public_reference)
            ."\nوضعیت پرداخت: ".TelegramHtml::escape(OrderStateLabels::payment($order->payment_status));
        $text .= "\n".TelegramHtml::bold('مبلغ قابل پرداخت: '.Money::formatToman($order->total_irr).' تومان');

        if ($latest?->status === 'rejected') {
            $text .= "\n\n".TelegramHtml::bold('❌ دلیل رد رسید قبلی: ').TelegramHtml::escape($latest->rejection_reason)
                ."\nمی‌توانید برای همین سفارش رسید جدید ارسال کنید.";
        }

        $keyboard = Keyboard::make();
        if ($order->order_status === 'awaiting_payment'
            && in_array($order->payment_status, ['awaiting_receipt', 'rejected'], true)) {
            $payment = $order->payment_instructions_snapshot;
            $text .= "\nشماره کارت: <code>".TelegramHtml::escape((string) ($payment['card_number'] ?? '')).'</code>'
                ."\nبه نام: ".TelegramHtml::escape((string) ($payment['card_holder_name'] ?? ''));
            if (is_string($payment['card_number'] ?? null) && $payment['card_number'] !== '') {
                $keyboard->addRow(Button::make('📋 کپی شماره کارت', copy_text: CopyTextButton::make($payment['card_number'])));
            }
            $text .= "\nفقط پس از انتقال وجه، عکس یا فایل تصویری رسید را ارسال کنید.";
            $keyboard->addRow(StorefrontButtons::primary(
                $order->payment_status === 'rejected' ? '📎 ارسال مجدد رسید' : '📎 ارسال رسید پرداخت',
                'receipt:upload:'.$order->id,
            ));
        }

        if ($order->payment_status === 'under_review') {
            $text .= "\n\nرسید دریافت شده است و در انتظار بررسی فروشگاه قرار دارد. نیازی به ارسال مجدد نیست.";
        }

        $keyboard->addRow(Button::make('📦 وضعیت سفارش', callback_data: 'myorder:'.$order->id));
        $keyboard->addRow(
            Button::make('⬅️ انتخاب سفارش', callback_data: 'menu:receipt'),
            Button::make('🏠 منوی اصلی', callback_data: 'menu:home'),
        );
        $bot->sendMessage(text: mb_substr($text, 0, 3900), parse_mode: ParseMode::HTML, reply_markup: $keyboard);
    }

    public function start(Nutgram $bot, Customer $customer, int $orderId): void
    {
        $order = $customer->orders()->find($orderId);

        if (! $order || $order->order_status !== 'awaiting_payment'
            || ! in_array($order->payment_status, ['awaiting_receipt', 'rejected'], true)) {
            $bot->sendMessage(
                text: 'برای این سفارش اکنون امکان ارسال رسید وجود ندارد. وضعیت سفارش را بررسی کنید.',
                reply_markup: Keyboard::make()->addRow(
                    Button::make('📦 سفارش‌های من', callback_data: 'menu:orders'),
                    Button::make('🏠 منوی اصلی', callback_data: 'menu:home'),
                ),
            );

            return;
        }

        $this->conversation->session($customer)->update([
            'state' => 'receipt:upload',
            'context' => ['order_id' => $order->id],
            'expires_at' => now()->addMinutes(30),
            'updated_at' => now(),
        ]);

        $bot->sendMessage(
            text: '📷 ارسال رسید برای سفارش '.$order->public_reference
                ."\nمبلغ: ".Money::formatToman($order->total_irr).' تومان'
                ."\n\nعکس JPG/PNG/WebP بفرستید (حداکثر ۸ مگابایت).",
            reply_markup: Keyboard::make()->addRow(
                Button::make('❌ لغو', callback_data: 'form:cancel'),
            ),
        );
    }

    public function handle(Nutgram $bot, Customer $customer): void
    {
        $session = $this->conversation->session($customer);
        if ($session->state !== 'receipt:upload' || ! $session->expires_at || $session->expires_at->isPast()) {
            $this->conversation->reset($customer);
            $bot->sendMessage(
                text: 'مهلت ارسال رسید تمام شده است. دوباره سفارش را انتخاب کنید.',
                reply_markup: Keyboard::make()->addRow(
                    StorefrontButtons::primary('🧾 انتخاب سفارش', 'menu:receipt'),
                    Button::make('🏠 منوی اصلی', callback_data: 'menu:home'),
                ),
            );

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

            $orderId = (int) $session->context['order_id'];
            $this->submit->execute($customer, $orderId, $path, $fileId);
            $this->conversation->reset($customer);
            $bot->sendMessage(
                text: TelegramHtml::bold('🧾 رسید شما دریافت شد؛ در انتظار بررسی پرداخت')
                    ."\n\n".TelegramHtml::quote('پس از بررسی و تأیید پرداخت، سفارش شما نهایی شده و نتیجه اعلام می‌شود.'),
                parse_mode: ParseMode::HTML,
                reply_markup: Keyboard::make()->addRow(
                    Button::make('📦 مشاهده وضعیت سفارش', callback_data: 'myorder:'.$orderId),
                    Button::make('🏠 منوی اصلی', callback_data: 'menu:home'),
                ),
            );
        } catch (ValidationException $exception) {
            $disk->delete($path);
            $bot->sendMessage(collect($exception->errors())->flatten()->first() ?? 'رسید پذیرفته نشد.');
        } catch (\Throwable $exception) {
            $disk->delete($path);
            // Do not log provider URLs: they can contain the Telegram bot token.
            $bot->sendMessage('دریافت یا ذخیره رسید موقتاً ناموفق بود؛ دوباره تلاش کنید.');
        }
    }
}
