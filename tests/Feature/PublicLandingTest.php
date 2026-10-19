<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicLandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_editorial_navigation_is_accessible_without_hiding_server_rendered_catalog(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('id="top" data-chapter', false)
            ->assertSee('id="products" data-chapter', false)
            ->assertSee('id="story" data-chapter', false)
            ->assertSee('id="order" data-chapter', false)
            ->assertSee('aria-label="فهرست موبایل بدون جاوااسکریپت"', false)
            ->assertSee('x-on:keydown="onMobileNavKeydown($event)"', false)
            ->assertSee('x-bind:aria-expanded="menuOpen.toString()"', false)
            ->assertSee('data-topography', false)
            ->assertSee('href="#top"', false);
    }

    public function test_guest_sees_only_published_orderable_products_and_exact_toman_prices(): void
    {
        $category = Category::create(['name' => 'خشکبار', 'slug' => 'nuts', 'is_active' => true]);
        $visible = $this->product($category, 'گردو طبیعی', 'walnuts', 13890000);
        $visible->variants()->create([
            'sku' => 'WALNUT-UNAVAILABLE', 'title' => 'موجود نیست',
            'sale_quantity' => 1, 'sale_unit' => 'kg', 'price_irr' => 100,
            'shipping_weight_grams' => 1000, 'is_available' => false,
        ]);
        $this->product($category, 'محصول مخفی', 'hidden', 400000)->update(['is_active' => false]);
        $this->product($category, 'محصول تمام‌شده', 'sold-out', 200000)->variants()->update(['is_available' => false]);
        $inactive = Category::create(['name' => 'دسته غیرفعال', 'slug' => 'inactive', 'is_active' => false]);
        $this->product($inactive, 'محصول دسته غیرفعال', 'inactive-item', 900000);

        $this->get('/')
            ->assertOk()->assertSee('گردو طبیعی')->assertSee('1,389,000')
            ->assertDontSee('محصول مخفی')->assertDontSee('محصول تمام‌شده')
            ->assertDontSee('محصول دسته غیرفعال')->assertDontSee('موجود نیست');
    }

    public function test_category_filter_and_pagination_are_public(): void
    {
        $nuts = Category::create(['name' => 'مغزها', 'slug' => 'nuts', 'is_active' => true]);
        $fruit = Category::create(['name' => 'میوه خشک', 'slug' => 'fruit', 'is_active' => true]);
        for ($index = 1; $index <= 13; $index++) {
            $this->product($nuts, 'گردو شماره '.$index, 'nuts-'.$index, 120000);
        }
        $this->product($fruit, 'آلو بخارا', 'plum', 180000);

        $this->get('/?category=fruit')->assertOk()->assertSee('آلو بخارا')->assertDontSee('گردو شماره 13');
        $this->get('/?category=nuts&page=2')->assertOk()->assertSee('گردو شماره 1')->assertDontSee('آلو بخارا');
        $this->get('/?category=unknown')->assertOk()->assertSee('آلو بخارا');
    }

    public function test_product_preview_contains_only_orderable_variants_and_safe_public_details(): void
    {
        $category = Category::create(['name' => 'خشکبار', 'slug' => 'nuts', 'is_active' => true]);
        $product = $this->product($category, 'گردوی دنا', 'dena-walnut', 13890000);
        $product->update([
            'summary' => 'گردوی طبیعی',
            'description' => 'توضیحات </script><script>alert(1)</script>',
        ]);
        $product->features()->create([
            'name' => 'خاستگاه', 'value' => 'سی‌سخت', 'sort_order' => 1,
        ]);
        $product->variants()->create([
            'sku' => 'HIDDEN-VARIANT', 'title' => 'تنوع غیرقابل سفارش',
            'sale_quantity' => 1, 'sale_unit' => 'kg', 'price_irr' => 100000,
            'shipping_weight_grams' => 1000, 'is_available' => false,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('id="denapax-product-preview"', false)
            ->assertSee('x-on:click="openProduct(', false)
            ->assertSee('id="landing-product-data"', false)
            ->assertDontSee('</script><script>alert(1)</script>', false)
            ->assertViewHas('productPreviews', function (array $previews) use ($product): bool {
                $preview = $previews[$product->id] ?? null;

                return $preview !== null
                    && $preview['title'] === 'گردوی دنا'
                    && $preview['description'] === 'توضیحات </script><script>alert(1)</script>'
                    && $preview['features'] === [['name' => 'خاستگاه', 'value' => 'سی‌سخت']]
                    && count($preview['variants']) === 1
                    && $preview['variants'][0]['price'] === '1,389,000'
                    && $preview['variants'][0]['size'] === '0.5 کیلوگرم'
                    && $preview['images'] === [];
            });
    }

    public function test_public_product_permalink_is_indexable_but_inactive_products_are_not(): void
    {
        $category = Category::create(['name' => 'خشکبار', 'slug' => 'nuts', 'is_active' => true]);
        $product = $this->product($category, 'گردو دنا', 'dena-walnut', 13890000);
        $product->update(['description' => 'گردو < ویژه >']);
        $product->variants()->create([
            'sku' => 'SOLD-OUT', 'title' => 'تنوع تمام‌شده',
            'sale_quantity' => 1, 'sale_unit' => 'kg',
            'price_irr' => 500000, 'shipping_weight_grams' => 1000,
            'is_available' => false,
        ]);

        $this->get(route('products.show', ['slug' => $product->slug]))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('products.show', ['slug' => $product->slug]).'">', false)
            ->assertSee('application/ld+json')
            ->assertSee('"priceCurrency":"IRR"', false)
            ->assertSee('"price":"13890000"', false)
            ->assertSee('1,389,000')
            ->assertSee('گردو &lt; ویژه &gt;', false)
            ->assertDontSee('تنوع تمام‌شده')
            ->assertDontSee('SOLD-OUT');

        $product->update(['is_active' => false]);
        $this->get(route('products.show', ['slug' => $product->slug]))->assertNotFound();
    }

    public function test_xml_sitemap_lists_only_publicly_orderable_products(): void
    {
        $category = Category::create(['name' => 'خشکبار', 'slug' => 'nuts', 'is_active' => true]);
        $active = $this->product($category, 'گردو', 'walnuts', 250000);
        $this->product($category, 'منتشرنشده', 'draft', 120000)->update(['is_active' => false]);
        $this->product($category, 'تمام‌شده', 'sold-out', 90000)->variants()->update(['is_available' => false]);
        $inactiveCategory = Category::create(['name' => 'غیرفعال', 'slug' => 'inactive', 'is_active' => false]);
        $this->product($inactiveCategory, 'مخفی', 'hidden', 60000);

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('home'), false)
            ->assertSee(route('products.show', ['slug' => $active->slug]), false)
            ->assertDontSee('/products/draft', false)
            ->assertDontSee('/products/sold-out', false)
            ->assertDontSee('/products/hidden', false);

        $this->get('/')->assertOk()->assertSee('href="'.route('products.show', ['slug' => $active->slug]).'"', false);
    }

    public function test_telegram_button_only_links_to_a_configured_valid_handle(): void
    {
        config()->set('services.telegram.bot_username', 'DenaPaxBot');
        $this->get('/')->assertOk()->assertSee('https://t.me/DenaPaxBot');

        config()->set('services.telegram.bot_username', 'bad/bot');
        $this->get('/')->assertOk()->assertDontSee('https://t.me/bad/bot')
            ->assertSee('لینک بات در حال آماده‌سازی است');
    }

    private function product(Category $category, string $title, string $slug, int $price): Product
    {
        $product = Product::create([
            'category_id' => $category->id, 'title' => $title, 'slug' => $slug,
            'summary' => 'محصولی از دنا', 'description' => 'توضیح نمونه',
            'is_active' => true,
        ]);
        $product->variants()->create([
            'sku' => $slug.'-500', 'title' => 'بسته نیم کیلویی',
            'sale_quantity' => 0.5, 'sale_unit' => 'kg',
            'price_irr' => $price, 'shipping_weight_grams' => 550,
            'is_available' => true,
        ]);

        return $product;
    }
}
