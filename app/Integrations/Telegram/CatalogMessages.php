<?php

namespace App\Integrations\Telegram;

use App\Application\Actions\BrowseCatalog;
use App\Models\ProductVariant;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton as Button;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup as Keyboard;

class CatalogMessages
{
    public function __construct(private readonly BrowseCatalog $catalog) {}

    public function categories(Nutgram $bot): void
    {
        $keyboard = Keyboard::make();

        foreach ($this->catalog->categories() as $category) {
            $keyboard->addRow(Button::make($category->name, callback_data: 'cat:'.$category->id.':1'));
        }

        $keyboard->addRow(Button::make('همه محصولات', callback_data: 'all:1'));
        $keyboard->addRow(Button::make('🔎 جستجو', callback_data: 'menu:search'));
        $keyboard->addRow(Button::make('🏠 منوی اصلی', callback_data: 'menu:home'));

        $bot->sendMessage(text: '🌰 دسته‌بندی‌های فروشگاه دنا', reply_markup: $keyboard);
    }

    public function products(Nutgram $bot, ?int $categoryId, ?string $term, int $page): void
    {
        $page = min(1000, max(1, $page));
        $items = $this->catalog->products($categoryId, $term, $page);
        $title = $term !== null ? 'نتایج جستجو: '.$term : 'محصولات فروشگاه';

        if ($items->total() === 0) {
            $bot->sendMessage('محصول فعالی پیدا نشد. عبارت دیگری جستجو کنید.');

            return;
        }

        if ($items->isEmpty()) {
            $bot->sendMessage('صفحه‌ای با این شماره وجود ندارد.');

            return;
        }

        $keyboard = Keyboard::make();
        foreach ($items as $product) {
            $keyboard->addRow(Button::make($product->title, callback_data: 'item:'.$product->id));

            $summary = '🌰 '.$product->title."\nدسته: ".$product->category->name."\n".$product->summary;
            foreach ($product->features->take(5) as $feature) {
                $summary .= "\n• ".$feature->name.': '.$feature->value;
            }
            $detailsButton = Keyboard::make()->addRow(
                Button::make('مشاهده تنوع‌ها و مشخصات', callback_data: 'item:'.$product->id),
            );

            $cover = $product->getFirstMedia('gallery');
            if ($cover) {
                $bot->sendPhoto(
                    photo: $cover->getFullUrl(),
                    caption: mb_substr($summary, 0, 900),
                    reply_markup: $detailsButton,
                );
            } else {
                $bot->sendMessage(
                    text: mb_substr($summary, 0, 3900),
                    reply_markup: $detailsButton,
                );
            }
        }

        $prefix = $term !== null ? 'search:' : ($categoryId === null ? 'all:' : 'cat:'.$categoryId.':');
        $buttons = [];
        if ($items->currentPage() > 1) {
            $buttons[] = Button::make('⬅️ قبلی', callback_data: $prefix.($items->currentPage() - 1));
        }
        if ($items->hasMorePages()) {
            $buttons[] = Button::make('بعدی ➡️', callback_data: $prefix.($items->currentPage() + 1));
        }
        if ($buttons !== []) {
            $keyboard->addRow(...$buttons);
        }

        $keyboard->addRow(
            Button::make('⬅️ دسته‌بندی‌ها', callback_data: 'menu:catalog'),
            Button::make('🏠 منوی اصلی', callback_data: 'menu:home'),
        );

        $bot->sendMessage(
            text: $title."\nصفحه ".$items->currentPage().' از '.$items->lastPage(),
            reply_markup: $keyboard,
        );
    }

    public function details(Nutgram $bot, int $productId): void
    {
        $product = $this->catalog->product($productId);

        if (! $product) {
            $bot->sendMessage('این محصول اکنون قابل سفارش نیست.');

            return;
        }

        $text = '🌿 '.$product->title."\n".$product->summary;
        if ($product->description) {
            $text .= "\n".$product->description;
        }
        if ($product->features->isNotEmpty()) {
            $text .= "\n\nویژگی‌ها:";
            foreach ($product->features as $feature) {
                $text .= "\n• ".$feature->name.': '.$feature->value;
            }
        }

        $text .= "\n\nتنوع‌ها و قیمت‌ها:";
        foreach ($product->variants as $variant) {
            $text .= "\n• ".$variant->title.' ('.$variant->sale_quantity.' '.$variant->sale_unit.') — '
                .$this->toman($variant->price_irr).' تومان';
        }

        $text .= "\n\nبرای افزودن هر تنوع به سبد روی دکمه آن بزنید.";
        $keyboard = Keyboard::make();
        if ($product->getMedia('gallery')->count() > 1) {
            $keyboard->addRow(Button::make('📷 تصاویر بیشتر', callback_data: 'gallery:'.$product->id.':1'));
        }
        foreach ($product->variants as $variant) {
            $keyboard->addRow(Button::make('🛒 افزودن '.$variant->title, callback_data: 'cart:add:'.$variant->id));
            if ($variant->getFirstMedia('variant-image')) {
                $keyboard->addRow(Button::make('تصویر '.$variant->title, callback_data: 'variant:'.$variant->id));
            }
        }
        $keyboard->addRow(Button::make('مشاهده سبد خرید', callback_data: 'menu:cart'));
        $keyboard->addRow(
            Button::make('⬅️ بازگشت به محصولات', callback_data: 'all:1'),
            Button::make('🏠 منوی اصلی', callback_data: 'menu:home'),
        );

        $media = $product->getFirstMedia('gallery');
        if ($media) {
            // Telegram photo captions are short; send full details separately.
            $bot->sendPhoto(
                photo: $media->getFullUrl(),
                caption: mb_substr($product->title."\n".$product->summary, 0, 900),
            );
        }
        $bot->sendMessage(text: mb_substr($text, 0, 3900), reply_markup: $keyboard);
    }

    public function gallery(Nutgram $bot, int $productId, int $index): void
    {
        $product = $this->catalog->product($productId);
        $images = $product?->getMedia('gallery');

        if (! $images || ! isset($images[$index])) {
            $bot->sendMessage('تصویر مورد نظر در دسترس نیست.');

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

        $bot->sendPhoto(
            photo: $images[$index]->getFullUrl(),
            caption: mb_substr($product->title.' — تصویر '.($index + 1).' از '.$images->count(), 0, 900),
            reply_markup: $keyboard,
        );
    }

    public function variantImage(Nutgram $bot, int $variantId): void
    {
        $variant = ProductVariant::query()
            ->where('is_available', true)
            ->whereHas('product', fn ($q) => $q->published())
            ->find($variantId);
        $media = $variant?->getFirstMedia('variant-image');

        if (! $media) {
            $bot->sendMessage('تصویر این تنوع موجود نیست.');

            return;
        }

        $bot->sendPhoto(
            photo: $media->getFullUrl(),
            caption: mb_substr($variant->product->title.' — '.$variant->title, 0, 900),
            reply_markup: Keyboard::make()->addRow(
                Button::make('⬅️ بازگشت به محصول', callback_data: 'item:'.$variant->product_id),
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
