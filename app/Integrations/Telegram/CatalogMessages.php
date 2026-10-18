<?php

namespace App\Integrations\Telegram;

use App\Application\Actions\BrowseCatalog;
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
        $keyboard->addRow(Button::make('جستجو', callback_data: 'menu:search'));

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

        $keyboard->addRow(Button::make('دسته‌بندی‌ها', callback_data: 'menu:catalog'));

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

        $text .= "\n\nافزودن به سبد خرید در فاز بعد فعال می‌شود.";
        $keyboard = Keyboard::make()->addRow(Button::make('بازگشت به محصولات', callback_data: 'all:1'));

        // The first gallery item is the cover. Other images can be opened through
        // the dedicated gallery navigation (no arbitrary external media URLs).
        $media = $product->getMedia('gallery')->first();
        if ($media) {
            $bot->sendPhoto(photo: $media->getFullUrl(), caption: mb_substr($text, 0, 950), reply_markup: $keyboard);
        } else {
            $bot->sendMessage(text: mb_substr($text, 0, 3900), reply_markup: $keyboard);
        }
    }

    private function toman(int $irr): string
    {
        return $irr % 10 === 0
            ? number_format(intdiv($irr, 10))
            : number_format($irr / 10, 1);
    }
}
