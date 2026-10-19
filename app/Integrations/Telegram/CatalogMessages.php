<?php

namespace App\Integrations\Telegram;

use App\Application\Actions\BrowseCatalog;
use App\Models\ProductVariant;
use App\Support\TelegramHtml;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Internal\InputFile;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton as Button;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup as Keyboard;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CatalogMessages
{
    public function __construct(private readonly BrowseCatalog $catalog) {}

    public function categories(Nutgram $bot): void
    {
        $keyboard = Keyboard::make();

        foreach ($this->catalog->categories() as $category) {
            $keyboard->addRow(Button::make($category->name, callback_data: 'cat:'.$category->id.':1'));
        }

        $keyboard->addRow(StorefrontButtons::primary('🌰 همه محصولات', 'all:1'));
        $keyboard->addRow(Button::make('🔎 جستجو', callback_data: 'menu:search'));
        $keyboard->addRow(Button::make('🏠 منوی اصلی', callback_data: 'menu:home'));

        $bot->sendMessage(text: '🌰 دسته‌بندی‌های فروشگاه دنا', reply_markup: $keyboard);
    }

    public function products(Nutgram $bot, ?int $categoryId, ?string $term, int $page): void
    {
        $items = $this->catalog->products($categoryId, $term, min(1000, max(1, $page)));
        $keyboard = Keyboard::make();

        if ($items->total() === 0) {
            $keyboard->addRow(
                Button::make('🔎 جستجوی دوباره', callback_data: 'menu:search'),
                StorefrontButtons::primary('🌰 محصولات', 'all:1'),
            );
            $keyboard->addRow(Button::make('🏠 منوی اصلی', callback_data: 'menu:home'));
            $bot->sendMessage(
                text: 'محصولی پیدا نشد. عبارت دیگری جستجو کنید یا محصولات را ببینید.',
                reply_markup: $keyboard,
            );

            return;
        }

        if ($items->isEmpty()) {
            $keyboard->addRow(
                Button::make('⬅️ صفحه اول', callback_data: $term !== null ? 'search:1' : ($categoryId === null ? 'all:1' : 'cat:'.$categoryId.':1')),
                Button::make('🏠 منوی اصلی', callback_data: 'menu:home'),
            );
            $bot->sendMessage(text: 'این صفحه در دسترس نیست؛ به صفحه اول برگردید.', reply_markup: $keyboard);

            return;
        }

        // Exactly one list message per page; product media is shown on demand
        // in details(), not sent in a batch for every catalog page.
        foreach ($items as $product) {
            $minimum = $product->variants->min('price_irr');
            $label = mb_substr($product->title, 0, 36);
            if ($minimum !== null) {
                $label .= ' · از '.$this->toman((int) $minimum).' تومان';
            }
            $keyboard->addRow(Button::make('🌰 '.$label, callback_data: 'item:'.$product->id));
        }

        $prefix = $term !== null ? 'search:' : ($categoryId === null ? 'all:' : 'cat:'.$categoryId.':');
        $pager = [];
        if ($items->currentPage() > 1) {
            $pager[] = Button::make('⬅️ قبلی', callback_data: $prefix.($items->currentPage() - 1));
        }
        if ($items->hasMorePages()) {
            $pager[] = Button::make('بعدی ➡️', callback_data: $prefix.($items->currentPage() + 1));
        }
        if ($pager !== []) {
            $keyboard->addRow(...$pager);
        }

        $keyboard->addRow(
            Button::make('⬅️ دسته‌بندی‌ها', callback_data: 'menu:catalog'),
            Button::make('🏠 منوی اصلی', callback_data: 'menu:home'),
        );

        $title = $term !== null ? '🔎 نتایج جستجو: '.mb_substr($term, 0, 80) : '🌰 محصولات فروشگاه';
        $pagination = $items->lastPage() > 1
            ? "\nصفحه ".$items->currentPage().' از '.$items->lastPage()
            : '';
        $bot->sendMessage(
            text: $title.$pagination."\n\nبرای دیدن تصویر، مشخصات و تنوع‌ها، محصول را انتخاب کنید.",
            reply_markup: $keyboard,
        );
    }

    public function details(Nutgram $bot, int $productId): void
    {
        $product = $this->catalog->product($productId);
        if (! $product) {
            $bot->sendMessage(
                text: 'این محصول اکنون قابل سفارش نیست. محصول دیگری انتخاب کنید.',
                reply_markup: Keyboard::make()->addRow(
                    StorefrontButtons::primary('🌰 محصولات', 'all:1'),
                    Button::make('🏠 منوی اصلی', callback_data: 'menu:home'),
                ),
            );

            return;
        }

        $summary = trim((string) $product->summary);
        $description = trim((string) $product->description);
        $normalize = static fn (string $value): string => mb_strtolower(
            preg_replace('/\\s+/u', ' ', trim($value)) ?? trim($value)
        );

        $text = '🌰 '.TelegramHtml::bold($product->title);
        if ($summary !== '') {
            $text .= "\n\n".TelegramHtml::escape($summary);
        }
        if ($description !== '' && $normalize($description) !== $normalize($summary)) {
            $text .= "\n".TelegramHtml::escape(mb_substr($description, 0, 550));
        }
        if ($product->features->isNotEmpty()) {
            $text .= "\n\n".TelegramHtml::bold('ویژگی‌ها');
            foreach ($product->features->take(5) as $feature) {
                $text .= "\n• ".TelegramHtml::escape($feature->name).': '.TelegramHtml::escape($feature->value);
            }
        }

        $text .= "\n\n".TelegramHtml::bold('تنوع‌های قابل سفارش');
        foreach ($product->variants as $variant) {
            // The merchant's variant title is customer-facing (e.g. "یک کیلو").
            // Avoid adding technical, redundant "1.000 kg" beside it.
            $text .= "\n• ".TelegramHtml::escape($variant->title)
                .' — '.$this->toman($variant->price_irr).' تومان';
        }
        $text .= "\n\nتنوع موردنظر را برای افزودن به سبد انتخاب کنید.";

        $keyboard = Keyboard::make();
        if ($product->getMedia('gallery')->count() > 1) {
            $keyboard->addRow(Button::make('📷 گالری تصاویر', callback_data: 'gallery:'.$product->id.':0'));
        }
        foreach ($product->variants as $variant) {
            $keyboard->addRow(StorefrontButtons::success('🛒 افزودن '.$variant->title, 'cart:add:'.$variant->id));
            if ($variant->getFirstMedia('variant-image')) {
                $keyboard->addRow(Button::make('📷 تصویر '.$variant->title, callback_data: 'variant:'.$variant->id));
            }
        }
        $keyboard->addRow(Button::make('🛒 سبد خرید', callback_data: 'menu:cart'));
        $keyboard->addRow(
            Button::make('⬅️ محصولات', callback_data: 'all:1'),
            Button::make('🏠 منوی اصلی', callback_data: 'menu:home'),
        );

        // Try product gallery first, falling back to a published variant photo.
        $media = $product->getFirstMedia('gallery')
            ?? $product->variants->first(fn ($variant) => $variant->getFirstMedia('variant-image') !== null)?->getFirstMedia('variant-image');

        if ($media && mb_strlen($text) <= 900
            && $this->sendMedia($bot, $media, $text, $keyboard, true)) {
            return;
        }

        if ($media) {
            // Keep variants in a separate message if the photo caption is too long.
            $this->sendMedia($bot, $media, mb_substr(TelegramHtml::escape($product->title), 0, 800), null, true);
        }
        $bot->sendMessage(text: mb_substr($text, 0, 3900), parse_mode: ParseMode::HTML, reply_markup: $keyboard);
    }

    public function gallery(Nutgram $bot, int $productId, int $index): void
    {
        $product = $this->catalog->product($productId);
        $images = $product?->getMedia('gallery');

        if (! $images || ! isset($images[$index])) {
            $bot->sendMessage(
                text: 'این تصویر در دسترس نیست. از فهرست محصولات ادامه دهید.',
                reply_markup: Keyboard::make()->addRow(
                    Button::make('⬅️ محصولات', callback_data: 'all:1'),
                    Button::make('🏠 منوی اصلی', callback_data: 'menu:home'),
                ),
            );

            return;
        }

        $keyboard = Keyboard::make();
        $pager = [];
        if ($index > 0) {
            $pager[] = Button::make('قبلی', callback_data: 'gallery:'.$productId.':'.($index - 1));
        }
        if ($index + 1 < $images->count()) {
            $pager[] = Button::make('بعدی', callback_data: 'gallery:'.$productId.':'.($index + 1));
        }
        if ($pager !== []) {
            $keyboard->addRow(...$pager);
        }
        $keyboard->addRow(
            Button::make('⬅️ مشخصات محصول', callback_data: 'item:'.$productId),
            Button::make('🏠 منوی اصلی', callback_data: 'menu:home'),
        );

        if (! $this->sendMedia($bot, $images[$index], mb_substr($product->title.' — تصویر '.($index + 1).' از '.$images->count(), 0, 900), $keyboard)) {
            $bot->sendMessage('تصویر موقتاً قابل نمایش نیست.', reply_markup: $keyboard);
        }
    }

    public function variantImage(Nutgram $bot, int $variantId): void
    {
        $variant = ProductVariant::query()
            ->where('is_available', true)
            ->whereHas('product', fn ($q) => $q->published())
            ->find($variantId);
        $media = $variant?->getFirstMedia('variant-image');

        if (! $media) {
            $bot->sendMessage(
                text: 'تصویر این تنوع در دسترس نیست.',
                reply_markup: Keyboard::make()->addRow(
                    Button::make('⬅️ محصولات', callback_data: 'all:1'),
                    Button::make('🏠 منوی اصلی', callback_data: 'menu:home'),
                ),
            );

            return;
        }

        if (! $this->sendMedia(
            $bot,
            $media,
            mb_substr($variant->product->title.' — '.$variant->title, 0, 900),
            Keyboard::make()->addRow(
                Button::make('⬅️ بازگشت به محصول', callback_data: 'item:'.$variant->product_id),
                Button::make('🏠 منوی اصلی', callback_data: 'menu:home'),
            ),
        )) {
            $bot->sendMessage('تصویر موقتاً قابل نمایش نیست.');
        }
    }

    private function sendMedia(Nutgram $bot, Media $media, string $caption, ?Keyboard $keyboard = null, bool $html = false): bool
    {
        try {
            // Upload directly from the server when the public storage URL is not
            // reachable by Telegram (e.g., CDN/proxy/storage-link mismatch).
            $path = $media->getPath();
            $photo = is_file($path) ? InputFile::make($path) : $media->getFullUrl();
            $bot->sendPhoto(
                photo: $photo,
                caption: $caption,
                parse_mode: $html ? ParseMode::HTML : null,
                reply_markup: $keyboard,
            );

            return true;
        } catch (\Throwable $exception) {
            // Do not leak storage locations, provider response URLs or bot tokens.
            Log::warning('Product photo delivery failed', ['error_type' => class_basename($exception)]);

            return false;
        }
    }

    private function toman(int $irr): string
    {
        return $irr % 10 === 0
            ? number_format(intdiv($irr, 10))
            : number_format($irr / 10, 1);
    }
}
