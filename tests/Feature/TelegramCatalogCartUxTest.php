<?php

namespace Tests\Feature;

use App\Application\Actions\IdentifyTelegramCustomer;
use App\Application\Actions\ManageCart;
use App\Models\Category;
use App\Models\Product;
use GuzzleHttp\Psr7\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Chat\Chat;
use SergiX44\Nutgram\Telegram\Types\User\User;
use SergiX44\Nutgram\Testing\FakeNutgram;
use Tests\TestCase;

class TelegramCatalogCartUxTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_page_shows_ten_product_choices_in_one_message_and_paginates(): void
    {
        for ($i = 1; $i <= 11; $i++) {
            $this->product('fig-'.$i, 130000);
        }

        $bot = $this->bot();
        $bot->hearCallbackQueryData('all:1')->reply();

        $messages = $this->messages($bot);
        $this->assertCount(1, $messages);
        $first = $messages[0];
        $this->assertStringContainsString('صفحه 1 از 2', $first['text']);
        $rows = $first['reply_markup']['inline_keyboard'];
        $this->assertCount(10, array_filter($rows, fn ($row) => str_starts_with($row[0]['callback_data'], 'item:')));
        $this->assertTrue(collect($rows)->flatten(1)->contains(fn ($button) => ($button['callback_data'] ?? '') === 'all:2'));
        $this->assertStringContainsString('13,000 تومان', $rows[0][0]['text']);

        $bot->hearCallbackQueryData('all:2')->reply();
        $second = $this->messages($bot);
        $this->assertCount(1, $second);
        $this->assertStringContainsString('صفحه 2 از 2', $second[0]['text']);
        $this->assertTrue(collect($second[0]['reply_markup']['inline_keyboard'])->flatten(1)
            ->contains(fn ($button) => ($button['callback_data'] ?? '') === 'all:1'));
    }

    public function test_product_details_only_offer_available_variants_and_empty_results_recover(): void
    {
        $product = $this->product('almonds', 240000);
        $product->variants()->create([
            'sku' => 'almonds-unavailable',
            'title' => 'Unavailable',
            'sale_quantity' => 1,
            'sale_unit' => 'pack',
            'price_irr' => 500000,
            'shipping_weight_grams' => 120,
            'is_available' => false,
        ]);
        $bot = $this->bot();
        $bot->hearCallbackQueryData('item:'.$product->id)->reply();

        $details = $this->messages($bot);
        $this->assertCount(1, $details);
        $this->assertStringContainsString('24,000 تومان', $details[0]['text']);
        $this->assertStringNotContainsString('Unavailable', $details[0]['text']);
        $buttons = collect($details[0]['reply_markup']['inline_keyboard'])->flatten(1);
        $this->assertSame(1, $buttons->filter(fn ($button) => str_starts_with($button['callback_data'] ?? '', 'cart:add:'))->count());

        $product->update(['is_active' => false]);
        $bot->hearCallbackQueryData('item:'.$product->id)->reply();
        $missing = $this->messages($bot);
        $this->assertCount(1, $missing);
        $this->assertStringContainsString('قابل سفارش نیست', $missing[0]['text']);
        $this->assertSame('all:1', $missing[0]['reply_markup']['inline_keyboard'][0][0]['callback_data']);

        $bot->hearCallbackQueryData('all:1')->reply();
        $empty = $this->messages($bot);
        $this->assertCount(1, $empty);
        $this->assertSame('menu:search', $empty[0]['reply_markup']['inline_keyboard'][0][0]['callback_data']);
    }

    public function test_cart_shows_compact_totals_and_nine_lines_per_page_without_changing_quantities(): void
    {
        $product = $this->product('mixed-nuts', 130000);
        $customer = app(IdentifyTelegramCustomer::class)->execute(898989, 898989, 'Buyer');
        $variants = [$product->variants()->firstOrFail()];
        for ($i = 2; $i <= 10; $i++) {
            $variants[] = $product->variants()->create([
                'sku' => 'mixed-'.$i,
                'title' => 'Pack '.$i,
                'sale_quantity' => 1,
                'sale_unit' => 'pack',
                'price_irr' => 130000,
                'shipping_weight_grams' => 120,
                'is_available' => true,
            ]);
        }
        foreach ($variants as $variant) {
            app(ManageCart::class)->add($customer, $variant->id);
        }

        $bot = $this->bot();
        $bot->hearText('/cart')->reply();
        $first = $this->messages($bot);
        $this->assertCount(1, $first);
        $this->assertStringContainsString('صفحه 1 از 2', $first[0]['text']);
        $this->assertStringContainsString('جمع سبد: 130,000 تومان', $first[0]['text']);
        $this->assertSame('HTML', $first[0]['parse_mode']);
        $this->assertStringContainsString('۱', $first[0]['reply_markup']['inline_keyboard'][0][0]['text']);
        $this->assertStringContainsString('× 13,000 = 13,000 تومان', $first[0]['text']);
        $this->assertSame('افزایش +۱', $first[0]['reply_markup']['inline_keyboard'][0][0]['text']);
        $this->assertSame('کاهش -۱', $first[0]['reply_markup']['inline_keyboard'][0][1]['text']);
        $this->assertTrue(collect($first[0]['reply_markup']['inline_keyboard'])->flatten(1)
            ->contains(fn ($button) => ($button['text'] ?? '') === '🛍️ افزودن محصول دیگر'
                && ($button['callback_data'] ?? '') === 'menu:catalog'));
        $this->assertSame(9, collect($first[0]['reply_markup']['inline_keyboard'])->flatten(1)
            ->filter(fn ($button) => str_starts_with($button['callback_data'] ?? '', 'cart:remove:'))->count());

        $bot->hearCallbackQueryData('cart:page:2')->reply();
        $second = $this->messages($bot);
        $this->assertCount(1, $second);
        $this->assertStringContainsString('صفحه 2 از 2', $second[0]['text']);
        $this->assertStringContainsString('جمع سبد: 130,000 تومان', $second[0]['text']);
        $this->assertSame(1, collect($second[0]['reply_markup']['inline_keyboard'])->flatten(1)
            ->filter(fn ($button) => str_starts_with($button['callback_data'] ?? '', 'cart:remove:'))->count());
        $this->assertSame(10, $customer->cart->items()->sum('quantity'));
    }

    public function test_single_cart_page_hides_page_counter_and_detail_escapes_repeated_text(): void
    {
        $product = $this->product('sour-cherry', 3500000);
        $product->update([
            'summary' => 'آلبالو <خوش‌طعم> & طبیعی',
            'description' => '  آلبالو <خوش‌طعم> & طبیعی  ',
        ]);
        $customer = app(IdentifyTelegramCustomer::class)->execute(898989, 898989, 'Buyer');
        app(ManageCart::class)->add($customer, $product->variants()->firstOrFail()->id);

        $bot = $this->bot();
        $bot->hearText('/cart')->reply();
        $cart = $this->messages($bot)[0];
        $this->assertStringNotContainsString('صفحه 1 از 1', $cart['text']);
        $this->assertStringContainsString('<b>جمع سبد: 350,000 تومان</b>', $cart['text']);

        $bot->hearCallbackQueryData('item:'.$product->id)->reply();
        $details = $this->messages($bot)[0];
        $this->assertSame('HTML', $details['parse_mode']);
        $this->assertSame(1, substr_count($details['text'], 'آلبالو &lt;خوش‌طعم&gt; &amp; طبیعی'));
        $this->assertStringNotContainsString('(1.000 kg)', $details['text']);
        $this->assertStringContainsString('<b>تنوع‌های قابل سفارش</b>', $details['text']);
    }

    public function test_empty_cart_has_direct_catalog_recovery(): void
    {
        $bot = $this->bot();
        $bot->hearText('/cart')->reply();

        $messages = $this->messages($bot);
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('سبد خرید شما خالی است', $messages[0]['text']);
        $this->assertSame('menu:catalog', $messages[0]['reply_markup']['inline_keyboard'][0][0]['callback_data']);
    }

    private function bot(): FakeNutgram
    {
        /** @var FakeNutgram $bot */
        $bot = app(Nutgram::class);
        $bot->setCommonUser(User::make(898989, false, 'Buyer'));
        $bot->setCommonChat(Chat::make(898989, 'private'));

        return $bot;
    }

    private function messages(FakeNutgram $bot): array
    {
        return collect($bot->getRequestHistory())
            ->map(fn ($record) => array_values($record)[0])
            ->filter(fn (Request $request) => $request->getUri()->getPath() === 'sendMessage')
            ->map(fn (Request $request) => json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR))
            ->values()
            ->all();
    }

    private function product(string $slug, int $price): Product
    {
        $category = Category::firstOrCreate(['slug' => 'dry-fruit'], [
            'name' => 'Dena dried fruits',
            'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'title' => $slug,
            'slug' => $slug,
            'summary' => 'Natural dried fruit',
            'description' => 'Carefully selected',
            'is_active' => true,
        ]);
        $product->variants()->create([
            'sku' => $slug,
            'title' => 'One pack',
            'sale_quantity' => 1,
            'sale_unit' => 'pack',
            'price_irr' => $price,
            'shipping_weight_grams' => 120,
            'is_available' => true,
        ]);

        return $product;
    }
}
