<?php

namespace Tests\Feature;

use App\Livewire\Store\HomeProductFeed;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HomeProductFeedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_homepage_renders_twelve_products_and_native_next_link_before_javascript(): void
    {
        $this->createProducts(26);

        $html = $this->get(route('home'))->assertOk()
            ->assertSee('wire:intersect.once.margin.200px="loadMore"', false)
            ->assertSee('صفحه بعد محصولات دناپکس')
            ->assertSee('href="'.route('home', ['page' => 2]).'#products"', false)
            ->assertSee('data-home-product-grid', false)
            ->assertSee('<noscript>', false)
            ->assertSee('<link rel="canonical" href="'.route('home').'">', false)
            ->getContent();

        $this->assertSame(12, substr_count($html, 'wire:key="home-product-'));
        $this->assertStringContainsString(route('products.show', ['slug' => 'feed-26']), $html);
        $this->assertStringNotContainsString('href="'.route('products.show', ['slug' => 'feed-14']).'"', $html);
    }

    public function test_direct_second_page_is_indexable_with_own_twelve_products_and_sequential_link(): void
    {
        $this->createProducts(27);

        $url = route('home', ['page' => 2]);
        $html = $this->get($url)->assertOk()
            ->assertSee('<link rel="canonical" href="'.$url.'">', false)
            ->assertSee('href="'.route('home', ['page' => 3]).'#products"', false)
            ->assertSee('feed-15', false)
            ->getContent();

        $this->assertSame(12, substr_count($html, 'wire:key="home-product-'));
        $this->assertStringNotContainsString('href="'.route('products.show', ['slug' => 'feed-27']).'"', $html);
        $this->assertStringContainsString('href="'.route('products.show', ['slug' => 'feed-15']).'"', $html);

        $this->get(route('home', ['page' => 4]))->assertNotFound();
    }

    public function test_livewire_appends_batches_then_switches_to_manual_loading_without_duplicate_cards(): void
    {
        $this->createProducts(40);

        $feed = Livewire::test(HomeProductFeed::class, ['startPage' => 1])
            ->assertSee('در حال بارگذاری محصولات بیشتر…');

        $this->assertSame(12, substr_count($feed->html(), 'wire:key="home-product-'));

        $feed->call('loadMore')->assertHasNoErrors();
        $this->assertSame(24, substr_count($feed->html(), 'wire:key="home-product-'));

        $feed->call('loadMore')->assertHasNoErrors()
            ->assertSee('نمایش محصولات بیشتر');

        $this->assertSame(36, substr_count($feed->html(), 'wire:key="home-product-'));
        $this->assertStringNotContainsString('wire:intersect.once.margin.200px', $feed->html());

        $feed->call('loadMore')->assertHasNoErrors()
            ->assertSee('همه محصولات این فهرست نمایش داده شد.');

        $this->assertSame(40, substr_count($feed->html(), 'wire:key="home-product-'));
        $this->assertSame(1, substr_count($feed->html(), 'wire:key="home-product-40"'));
        $this->assertSame(1, substr_count($feed->html(), 'wire:key="home-product-1"'));
    }

    public function test_hidden_or_unavailable_items_are_never_rendered_in_the_home_feed(): void
    {
        $products = $this->createProducts(2);

        $products[0]->update(['is_active' => false]);
        $products[1]->variants()->update(['is_available' => false]);

        $this->get(route('home'))->assertOk()
            ->assertSee('محصولی برای نمایش پیدا نشد')
            ->assertDontSee('wire:key="home-product-', false);

        Livewire::test(HomeProductFeed::class)->assertDontSee('نمایش محصولات بیشتر');
    }

    /**
     * @return list<Product>
     */
    private function createProducts(int $count): array
    {
        $category = Category::firstOrCreate(
            ['slug' => 'feed-category'],
            ['name' => 'خشکبار', 'is_active' => true],
        );

        $products = [];

        for ($i = 1; $i <= $count; $i++) {
            $slug = 'feed-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $product = Product::create([
                'category_id' => $category->id,
                'title' => 'خشکبار شماره '.$i,
                'slug' => $slug,
                'summary' => 'خشکبار محلی',
                'is_active' => true,
            ]);
            $product->variants()->create([
                'sku' => 'FEED-'.$i,
                'title' => 'بسته ۵۰۰ گرمی',
                'sale_quantity' => 500,
                'sale_unit' => 'g',
                'price_irr' => 2000000,
                'shipping_weight_grams' => 500,
                'is_available' => true,
            ]);
            $products[] = $product;
        }

        return $products;
    }
}
