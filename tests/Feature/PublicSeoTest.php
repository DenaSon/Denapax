<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_homepage_has_store_identity_and_not_an_unused_remote_image_preload(): void
    {
        $this->get(route('home'))->assertOk()
            ->assertSee('<meta name="robots" content="index,follow,max-image-preview:large">', false)
            ->assertSee('خرید سوغات دنا، خشکبار و محصولات محلی سی‌سخت')
            ->assertSee('"@type":"OnlineStore"', false)
            ->assertSee('"@type":"WebSite"', false)
            ->assertSee('denapax-mark.svg')
            ->assertSee('rel="icon" type="image/svg+xml" sizes="any"', false)
            ->assertDontSee('upload.wikimedia.org');
    }

    public function test_public_category_has_own_indexable_canonical_breadcrumbs_and_filtered_products(): void
    {
        $nuts = Category::create(['name' => 'خشکبار', 'slug' => 'nuts', 'is_active' => true]);
        $fruit = Category::create(['name' => 'میوه خشک', 'slug' => 'fruits', 'is_active' => true]);
        $visible = $this->product($nuts, 'گردوی دنا', 'dena-walnuts');
        $other = $this->product($fruit, 'برگ هلو', 'peach-leaf');
        $hidden = $this->product($nuts, 'گردوی مخفی', 'hidden-walnut');
        $hidden->update(['is_active' => false]);

        $url = route('categories.show', ['slug' => 'nuts']);
        $this->get($url)->assertOk()
            ->assertSee('<title>خرید خشکبار | محصولات دناپکس</title>', false)
            ->assertSee('<meta name="robots" content="index,follow,max-image-preview:large">', false)
            ->assertSee('<link rel="canonical" href="'.$url.'">', false)
            ->assertSee('"@type":"CollectionPage"', false)
            ->assertSee('"@type":"BreadcrumbList"', false)
            ->assertSee('href="'.route('products.show', ['slug' => $visible->slug]).'"', false)
            ->assertDontSee($other->title)
            ->assertDontSee($hidden->title)
            ->assertDontSee('noindex,nofollow');
    }

    public function test_empty_inactive_or_exhausted_categories_are_not_public_or_in_sitemap(): void
    {
        $active = Category::create(['name' => 'خشکبار', 'slug' => 'available', 'is_active' => true]);
        $inactive = Category::create(['name' => 'مخفی', 'slug' => 'inactive', 'is_active' => false]);
        $empty = Category::create(['name' => 'بدون محصول', 'slug' => 'empty', 'is_active' => true]);
        $soldOut = Category::create(['name' => 'اتمام موجودی', 'slug' => 'sold-out', 'is_active' => true]);

        $item = $this->product($active, 'گردوی دنا', 'available-walnuts');
        $this->product($inactive, 'کالای مخفی', 'inactive-walnuts');
        $this->product($soldOut, 'کالای تمام‌شده', 'sold-out-walnuts')->variants()->update(['is_available' => false]);

        foreach ([$inactive, $empty, $soldOut] as $category) {
            $this->get(route('categories.show', ['slug' => $category->slug]))->assertNotFound();
        }

        $this->get(route('sitemap'))->assertOk()
            ->assertSee(route('categories.show', ['slug' => $active->slug]), false)
            ->assertSee(route('products.show', ['slug' => $item->slug]), false)
            ->assertDontSee('/categories/inactive', false)
            ->assertDontSee('/categories/empty', false)
            ->assertDontSee('/categories/sold-out', false)
            ->assertDontSee('/products/sold-out-walnuts', false);
    }

    public function test_category_pagination_uses_self_canonical_and_rejects_out_of_range_page(): void
    {
        $category = Category::create(['name' => 'خشکبار', 'slug' => 'nuts', 'is_active' => true]);

        for ($i = 1; $i <= 13; $i++) {
            $this->product($category, 'گردو '.$i, 'walnut-'.$i);
        }

        $secondPage = route('categories.show', ['slug' => $category->slug, 'page' => 2]);
        $this->get($secondPage)->assertOk()
            ->assertSee('<link rel="canonical" href="'.$secondPage.'">', false)
            ->assertSee('صفحه 2 از 2');

        $this->get(route('categories.show', ['slug' => $category->slug, 'page' => 3]))
            ->assertNotFound();
    }

    public function test_product_markup_preserves_real_irr_price_and_links_to_category_without_fabricated_policy(): void
    {
        $category = Category::create(['name' => 'خشکبار', 'slug' => 'nuts', 'is_active' => true]);
        $product = $this->product($category, 'مغز گردوی دنا', 'walnut-kernels');

        $this->get(route('products.show', ['slug' => $product->slug]))->assertOk()
            ->assertSee('<title>خرید مغز گردوی دنا | دناپکس</title>', false)
            ->assertSee('"@type":"Product"', false)
            ->assertSee('"@type":"BreadcrumbList"', false)
            ->assertSee('"priceCurrency":"IRR"', false)
            ->assertSee('"price":"13890000"', false)
            ->assertSee('href="'.route('categories.show', ['slug' => $category->slug]).'"', false)
            ->assertDontSee('aggregateRating')
            ->assertDontSee('hasMerchantReturnPolicy')
            ->assertDontSee('shippingDetails');
    }

    public function test_search_and_customer_pages_remain_not_indexable(): void
    {
        $this->get(route('search'))->assertOk()
            ->assertSee('<meta name="robots" content="noindex,nofollow">', false);
        $this->get(route('cart.index'))->assertOk()
            ->assertSee('<meta name="robots" content="noindex,nofollow">', false);
        $this->get(route('customer.login'))->assertOk()
            ->assertSee('<meta name="robots" content="noindex,nofollow">', false);
    }

    private function product(Category $category, string $title, string $slug): Product
    {
        $product = Product::create([
            'category_id' => $category->id,
            'title' => $title,
            'slug' => $slug,
            'summary' => 'مشخصات '.$title,
            'is_active' => true,
        ]);

        $product->variants()->create([
            'sku' => 'SEO-'.strtoupper($slug),
            'title' => 'بسته ۵۰۰ گرمی',
            'sale_quantity' => 500,
            'sale_unit' => 'g',
            'price_irr' => 13890000,
            'shipping_weight_grams' => 550,
            'is_available' => true,
        ]);

        return $product;
    }
}
