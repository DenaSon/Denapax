<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_public_layouts_use_one_livewire_navigation_runtime(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('resources/js/storefront.js')
            ->assertSee('wire:navigate', false)
            ->assertSee('livewire', false);

        $this->get(route('cart.index'))->assertOk()
            ->assertSee('resources/js/storefront.js')
            ->assertSee('wire:navigate', false);

        $this->assertStringNotContainsString("import Alpine from 'alpinejs'", file_get_contents(resource_path('js/landing.js')));
        $this->assertStringContainsString("document.addEventListener('alpine:init'", file_get_contents(resource_path('js/landing.js')));
        $this->assertStringContainsString("document.addEventListener('livewire:navigated'", file_get_contents(resource_path('js/product-gallery.js')));
    }

    public function test_chapter_navigation_matches_three_landing_menu_sections(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(1, preg_match('/<nav class="landing-chapter-rail"[^>]*>(.*?)<\\/nav>/s', $html, $matches));
        $rail = $matches[1];

        $this->assertSame(3, substr_count($rail, '<a href='));
        $this->assertStringContainsString('href="#products"', $rail);
        $this->assertStringContainsString('href="#story"', $rail);
        $this->assertStringContainsString('href="#order"', $rail);
        $this->assertStringNotContainsString('href="#top"', $rail);
        $this->assertStringContainsString('href="'.route('cart.index').'"', $html);
    }

    public function test_interactive_buttons_have_pointer_styles_without_exposing_disabled_actions(): void
    {
        $css = file_get_contents(resource_path('css/landing.css'));
        $this->assertStringContainsString('button:not(:disabled)', $css);
        $this->assertStringContainsString('cursor: pointer', $css);
        $this->assertStringContainsString('cursor: not-allowed', $css);

        $adminCss = file_get_contents(resource_path('css/app.css'));
        $this->assertStringContainsString('.admin-shell :is(a[href]', $adminCss);
    }

    public function test_refreshed_landing_keeps_real_category_links_and_compact_static_sections(): void
    {
        $nuts = Category::create(['name' => 'خشکبار', 'slug' => 'nuts', 'is_active' => true]);
        $this->product($nuts, 'گردوی دنا', 'dena-walnuts', 450000);

        $this->get('/')
            ->assertOk()
            ->assertSee('گردوی دنا')
            ->assertSee('data-catalog-link href="'.route('home', ['category' => 'nuts']).'#products"', false)
            ->assertSee('x-on:click="handleCatalogClick($event)"', false)
            ->assertSee('id="products" data-chapter', false)
            ->assertSee('id="order" data-chapter', false)
            ->assertDontSee('هر محصول، روایتی از یک جاست.')
            ->assertDontSee('از اینجا')
            ->assertDontSee('به تلگرام.');
    }

    public function test_editorial_landing_has_clear_customer_assurance_and_consistent_product_cards(): void
    {
        $category = Category::create(['name' => 'خشکبار', 'slug' => 'nuts', 'is_active' => true]);
        $this->product($category, 'گردوی دنا', 'walnuts-new', 13890000);
        $this->product($category, 'مغز گردوی دنا', 'walnut-kernels', 14800000);

        $this->get('/')->assertOk()
            ->assertSee('aria-label="ویژگی‌های خرید از دناپکس"', false)
            ->assertSee('وزن، مشخصات و قیمت به تومان')
            ->assertSee('class="landing-story-panel"', false)
            ->assertSee('از انتخاب محصول تا ثبت سفارش')
            ->assertSee('class="product-card-action', false)
            ->assertDontSee('product-card-featured')
            ->assertDontSee('DENAPAX / SELECTED');
    }

    public function test_editorial_styles_respect_mobile_and_reduced_motion(): void
    {
        $css = file_get_contents(resource_path('css/landing.css'));

        $this->assertStringContainsString('.landing-assurance-grid', $css);
        $this->assertStringContainsString('.landing-story-panel', $css);
        $this->assertStringContainsString('.landing:not(.store-shell) .product-card', $css);
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $css);
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

    public function test_product_gallery_is_accessible_without_images_or_javascript(): void
    {
        $category = Category::create(['name' => 'خشکبار', 'slug' => 'nuts', 'is_active' => true]);
        $product = $this->product($category, 'گردو بدون عکس', 'walnuts-without-photo', 1250000);

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('data-product-gallery', false)
            ->assertSee('تصویر این محصول هنوز ثبت نشده است')
            ->assertDontSee('data-gallery-open', false)
            ->assertDontSee('data-gallery-lightbox', false);
    }

    public function test_single_photo_is_linkable_without_javascript_and_opens_native_lightbox_when_available(): void
    {
        Storage::fake('public');
        $category = Category::create(['name' => 'خشکبار', 'slug' => 'nuts', 'is_active' => true]);
        $product = $this->product($category, 'گردو با یک عکس', 'single-walnut-photo', 1250000);
        $product->addMedia(UploadedFile::fake()->image('only.jpg'))
            ->toMediaCollection('gallery', 'public');

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('data-gallery-open', false)
            ->assertSee('data-gallery-lightbox', false)
            ->assertSee('product-gallery-single-fallback')
            ->assertDontSee('data-gallery-step', false)
            ->assertDontSee('data-gallery-thumbs', false);
    }

    public function test_mobile_gallery_includes_variant_photos_and_maps_radio_selection_to_exact_image(): void
    {
        Storage::fake('public');
        $category = Category::create(['name' => 'خشکبار', 'slug' => 'nuts', 'is_active' => true]);
        $product = $this->product($category, 'گردوی دنا', 'mobile-walnut-gallery', 13890000);

        $product->addMedia(UploadedFile::fake()->image('cover.jpg'))
            ->toMediaCollection('gallery', 'public');
        $product->addMedia(UploadedFile::fake()->image('detail.jpg'))
            ->toMediaCollection('gallery', 'public');

        $variant = $product->variants()->create([
            'sku' => 'DEN-WAL-ONE', 'title' => 'بسته یک کیلویی',
            'sale_quantity' => 1, 'sale_unit' => 'kg',
            'price_irr' => 24000000, 'shipping_weight_grams' => 1050,
            'is_available' => true,
        ]);
        $variant->addMedia(UploadedFile::fake()->image('variant.jpg'))
            ->toMediaCollection('variant-image', 'public');

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('data-gallery-thumbs', false)
            ->assertSee('data-gallery-step="1"', false)
            ->assertSee('data-lightbox-viewport', false)
            ->assertSee('data-lightbox-zoom-in', false)
            ->assertSee('data-image-full-url=', false)
            ->assertSee('data-gallery-index="2"', false)
            ->assertSee('data-variant-photo-index="2"', false)
            ->assertSee('aria-current="true"', false)
            ->assertSee('loading="lazy"', false)
            ->assertViewHas('photos', fn ($photos): bool => $photos->count() === 3)
            ->assertViewHas('variantPhotoIndexes', fn (array $indexes): bool => $indexes[$variant->id] === 2);

        $this->assertStringContainsString('storefront.js', file_get_contents(base_path('vite.config.js')));
        $this->assertStringContainsString("import './product-gallery';", file_get_contents(resource_path('js/storefront.js')));
        $this->assertStringContainsString(
            "@source '../js/product-gallery.js';",
            file_get_contents(resource_path('css/landing.css')),
        );
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

    public function test_product_preview_posts_selected_variant_to_existing_guest_cart_endpoint(): void
    {
        $category = Category::create(['name' => 'خشکبار', 'slug' => 'nuts', 'is_active' => true]);
        $product = $this->product($category, 'گردو دنا', 'dena-walnut', 500000);
        $variantId = $product->variants()->firstOrFail()->id;

        $this->get('/')->assertOk()
            ->assertSee('action="'.route('cart.add').'"', false)
            ->assertSee('x-bind:value="preview.variants[previewVariantIndex].id"', false)
            ->assertSee('"id":'.$variantId, false)
            ->assertSee('name="quantity"', false)
            ->assertSee('افزودن به سبد خرید');

        $this->post(route('cart.add'), [
            'variant_id' => $variantId,
            'quantity' => 2,
        ])->assertRedirect(route('cart.index'));

        $this->get('/')->assertOk()
            ->assertViewHas('cartQuantity', 2)
            ->assertSee('تعداد اقلام سبد خرید: 2');

        $this->get(route('cart.index'))->assertOk()
            ->assertSee('تعداد اقلام سبد خرید: 2')
            ->assertSee('گردو دنا');
    }

    public function test_signed_in_cart_quantity_uses_existing_cart_without_creating_empty_one(): void
    {
        $customer = Customer::create(['mobile' => '09120000091', 'mobile_verified_at' => now()]);
        $this->actingAs($customer, 'customer');

        $this->assertFalse($customer->cart()->exists());
        $this->get('/')->assertOk()->assertViewHas('cartQuantity', 0);
        $this->assertFalse($customer->cart()->exists());

        $category = Category::create(['name' => 'خشکبار', 'slug' => 'nuts', 'is_active' => true]);
        $product = $this->product($category, 'مغز گردو دنا', 'dena-kernels', 790000);

        $this->post(route('cart.add'), [
            'variant_id' => $product->variants()->firstOrFail()->id,
            'quantity' => 3,
        ])->assertRedirect(route('cart.index'));

        $this->get('/')->assertOk()->assertViewHas('cartQuantity', 3);
        $this->get(route('cart.index'))->assertOk()->assertSee('تعداد اقلام سبد خرید: 3');
    }

    public function test_public_catalog_has_web_cart_links_without_bot_dependencies(): void
    {
        $this->get('/')->assertOk()
            ->assertSee(route('cart.index'))
            ->assertDontSee('https://t.me/')
            ->assertDontSee('تلگرام');

        $this->get('/cart')->assertOk()->assertSee('سبد خرید');
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
