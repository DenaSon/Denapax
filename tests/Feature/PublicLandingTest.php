<?php

namespace Tests\Feature;

use App\Application\Actions\BrowseCatalog;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\StoreSetting;
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
        $this->get('/')->assertOk()->assertSee('wire:navigate', false)->assertSee('livewire', false);
        $this->get(route('cart.index'))->assertOk()->assertSee('wire:navigate', false);

        $this->assertStringContainsString('resources/js/storefront.js', file_get_contents(resource_path('views/landing/index.blade.php')));
        $this->assertStringContainsString('resources/js/storefront.js', file_get_contents(resource_path('views/layouts/store.blade.php')));

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

    public function test_refreshed_landing_uses_livewire_category_navigation_and_compact_static_sections(): void
    {
        $nuts = Category::create(['name' => 'خشکبار', 'slug' => 'nuts', 'is_active' => true]);
        $this->product($nuts, 'گردوی دنا', 'dena-walnuts', 450000);

        $this->get('/')
            ->assertOk()
            ->assertSee('گردوی دنا')
            ->assertSee('wire:navigate href="'.route('home', ['category' => 'nuts']).'#products"', false)
            ->assertDontSee('data-catalog-link', false)
            ->assertDontSee('handleCatalogClick', false)
            ->assertSee('id="products" data-chapter', false)
            ->assertSee('id="order" data-chapter', false)
            ->assertDontSee('هر محصول، روایتی از یک جاست.')
            ->assertDontSee('از اینجا')
            ->assertDontSee('به تلگرام.');
    }

    public function test_search_form_uses_a_dedicated_results_page_and_livewire_navigation(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('action="'.route('search').'"', false)
            ->assertSee('x-data="storeSearch"', false)
            ->assertSee('x-on:submit.prevent="submit($event)"', false)
            ->assertDontSee('denapax-product-preview', false)
            ->assertDontSee('landing-product-data', false);

        $script = file_get_contents(resource_path('js/landing.js'));
        $this->assertStringContainsString("Alpine.data('storeSearch'", $script);
        $this->assertStringContainsString('window.Livewire.navigate(url.toString())', $script);
        $this->assertStringNotContainsString('loadCatalog', $script);
        $this->assertStringNotContainsString('innerHTML', $script);
        $this->assertStringNotContainsString('openProduct', $script);
    }

    public function test_catalog_search_matches_persian_and_arabic_yah_and_kaf_forms_for_sellable_products(): void
    {
        $category = Category::create(['name' => 'خشکبار', 'slug' => 'nuts', 'is_active' => true]);
        $product = $this->product($category, 'مغز گردوي دنا', 'walnut-arabic-yeh', 13890000);

        $this->get(route('search', ['q' => 'گردو']))->assertOk()->assertSee($product->title);
        $this->get(route('search', ['q' => 'گردوی']))->assertOk()->assertSee($product->title);
        $this->get(route('search', ['q' => 'گردوي']))->assertOk()->assertSee($product->title);
        $this->get(route('search', ['q' => 'دنا']))->assertOk()->assertSee($product->title);
        $this->get(route('search', ['q' => 'گردو دنا']))->assertOk()->assertSee($product->title);
        $this->get(route('search', ['q' => 'walnut-arabic-yeh']))->assertOk()->assertSee($product->title);
        $this->get(route('search', ['q' => 'خشکبار']))->assertOk()->assertSee($product->title);

        $catalog = app(BrowseCatalog::class);
        $this->assertTrue($catalog->products(null, 'دنا')->getCollection()->contains('id', $product->id));
        $this->assertTrue($catalog->products(null, 'گردو دنا')->getCollection()->contains('id', $product->id));

        $arabicKaf = $this->product($category, 'كاجوی دنا', 'cashew-arabic-kaf', 9800000);
        $this->get(route('search', ['q' => 'کاجو']))->assertOk()->assertSee($arabicKaf->title);
    }

    public function test_homepage_product_cards_navigate_directly_to_indexable_product_pages(): void
    {
        $category = Category::create(['name' => 'خشکبار', 'slug' => 'nuts', 'is_active' => true]);
        $product = $this->product($category, 'گردوی دنا', 'direct-product-navigation', 13890000);

        $this->get('/')->assertOk()
            ->assertSee('wire:navigate href="'.route('products.show', ['slug' => $product->slug]).'"', false)
            ->assertDontSee('openProduct(', false)
            ->assertDontSee('aria-haspopup="dialog"', false)
            ->assertDontSee('denapax-product-preview', false);
    }

    public function test_storefront_footers_show_support_and_developer_credit_without_external_photo_attribution(): void
    {
        StoreSetting::create([
            'store_name' => 'دناپکس',
            'support_contact' => '09120000000',
            'card_number' => '6037991234567890',
            'card_holder_name' => 'مدیر دناپکس',
        ]);
        $category = Category::create(['name' => 'خشکبار', 'slug' => 'nuts', 'is_active' => true]);
        $product = $this->product($category, 'گردوی دنا', 'footer-walnut', 13890000);

        $this->get('/')->assertOk()
            ->assertSee('Mohammad Asadi')
            ->assertSee('https://t.me/m_asadix7', false)
            ->assertSee('طراحی و توسعه:')
            ->assertDontSee('Farid Atar · CC BY 3.0')
            ->assertSee('tel:09120000000', false);

        $this->get(route('products.show', $product->slug))->assertOk()
            ->assertSee('DenaPax')
            ->assertDontSee('DenaPax.')
            ->assertSee('tel:09120000000', false);

        $this->get(route('cart.index'))->assertOk()
            ->assertSee('tel:09120000000', false)
            ->assertSee('Mohammad Asadi');
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
            ->assertSee('مراحل سفارش')
            ->assertSee('۱. انتخاب محصول · ۲. مشاهده سبد خرید · ۳. ورود و ثبت نشانی · ۴. انتخاب روش پرداخت')
            ->assertDontSee('از انتخاب محصول تا ثبت سفارش')
            ->assertSee('class="product-card-action', false)
            ->assertDontSee('product-card-featured')
            ->assertDontSee('DENAPAX / SELECTED');
    }

    public function test_editorial_styles_respect_mobile_and_reduced_motion(): void
    {
        $css = file_get_contents(resource_path('css/landing.css'));

        $this->assertStringContainsString('.landing-assurance-grid', $css);
        $this->assertStringContainsString('.landing-story-panel', $css);
        $this->assertStringNotContainsString('upload.wikimedia.org/wikipedia/commons/3/35/Dena_mountains', $css);
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

    public function test_homepage_does_not_render_product_preview_payload_or_dialog(): void
    {
        $category = Category::create(['name' => 'خشکبار', 'slug' => 'nuts', 'is_active' => true]);
        $product = $this->product($category, 'گردوی دنا', 'no-home-preview-payload', 13890000);
        $product->update(['description' => 'توضیحات </script><script>alert(1)</script>']);

        $this->get('/')->assertOk()
            ->assertSee('گردوی دنا')
            ->assertDontSee('denapax-product-preview', false)
            ->assertDontSee('landing-product-data', false)
            ->assertDontSee('productPreviews', false)
            ->assertDontSee('</script><script>alert(1)</script>', false);

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('توضیحات &lt;/script&gt;&lt;script&gt;alert(1)&lt;/script&gt;', false);
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

    public function test_single_photo_gallery_is_non_zoomable_and_keeps_a_plain_product_image(): void
    {
        Storage::fake('public');
        $category = Category::create(['name' => 'خشکبار', 'slug' => 'nuts', 'is_active' => true]);
        $product = $this->product($category, 'گردو با یک عکس', 'single-walnut-photo', 1250000);
        $product->addMedia(UploadedFile::fake()->image('only.jpg'))
            ->toMediaCollection('gallery', 'public');

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('data-gallery-main', false)
            ->assertSee('data-gallery-thumb', false)
            ->assertDontSee('data-gallery-open', false)
            ->assertDontSee('data-gallery-lightbox', false)
            ->assertDontSee('product-gallery-single-fallback')
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
            ->assertSee('data-image-url=', false)
            ->assertDontSee('data-gallery-lightbox', false)
            ->assertDontSee('data-lightbox-zoom-in', false)
            ->assertDontSee('data-image-full-url=', false)
            ->assertSee('data-gallery-index="2"', false)
            ->assertSee('data-variant-photo-index="2"', false)
            ->assertSee('aria-current="true"', false)
            ->assertSee('loading="lazy"', false)
            ->assertViewHas('photos', fn ($photos): bool => $photos->count() === 3)
            ->assertViewHas('variantPhotoIndexes', fn (array $indexes): bool => $indexes[$variant->id] === 2);

        $this->assertStringContainsString('storefront.js', file_get_contents(base_path('vite.config.js')));
        $galleryScript = file_get_contents(resource_path('js/product-gallery.js'));
        $this->assertStringContainsString('function show(index, scrollThumb = true)', $galleryScript);
        $this->assertStringContainsString('touchstart', $galleryScript);
        $this->assertStringNotContainsString('updateZoomBounds', $galleryScript);
        $this->assertStringNotContainsString('pinch', $galleryScript);
        $this->assertStringContainsString('max-width:560px', str_replace(' ', '', file_get_contents(resource_path('css/landing.css'))));

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

    public function test_product_detail_shows_live_cart_state_and_keeps_native_post_fallback(): void
    {
        $category = Category::create(['name' => 'خشکبار', 'slug' => 'nuts', 'is_active' => true]);
        $product = $this->product($category, 'گردوی دنا', 'product-live-cart', 5000000);
        $firstVariant = $product->variants()->firstOrFail();
        $secondVariant = $product->variants()->create([
            'sku' => 'PRODUCT-LIVE-CART-ONE-KG',
            'title' => 'بسته یک کیلویی',
            'sale_quantity' => 1,
            'sale_unit' => 'kg',
            'price_irr' => 7200000,
            'shipping_weight_grams' => 1050,
            'is_available' => true,
        ]);

        $this->post(route('cart.add'), [
            'variant_id' => $firstVariant->id,
            'quantity' => 2,
        ])->assertRedirect(route('cart.index'));

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('x-data="productPurchase(', false)
            ->assertSee('x-on:submit="handleAddSubmit($event)"', false)
            ->assertSee('x-on:cart-added.window="handleCartAdded($event)"', false)
            ->assertSee('x-on:cart-quantity-updated.window="handleCartQuantityUpdated($event)"', false)
            ->assertSee('data-product-purchase-panel', false)
            ->assertSee('جمع قیمت برای', false)
            ->assertSee('1,000,000')
            ->assertSee('x-show="hasSelectedInCart"', false)
            ->assertSee('x-on:click="setSelectedQuantity(selectedCartQuantity + 1)"', false)
            ->assertSee('name="variant_id"', false)
            ->assertSee('name="quantity"', false)
            ->assertSee('method="POST"', false)
            ->assertSee('action="'.route('cart.add').'"', false)
            ->assertSee('افزودن با بارگذاری صفحه')
            ->assertViewHas('cartQuantities', fn ($quantities): bool => (int) ($quantities->{$firstVariant->id} ?? 0) === 2)
            ->assertViewHas('variantPurchaseData', fn (array $variants): bool => count($variants) === 2
                && $variants[0]['id'] === $firstVariant->id
                && $variants[0]['priceLabel'] === '500,000'
                && $variants[0]['priceToman'] === '500000'
                && $variants[1]['id'] === $secondVariant->id
                && $variants[1]['priceLabel'] === '720,000'
                && $variants[1]['priceToman'] === '720000');

        $this->assertStringContainsString('<livewire:store.add-to-cart />', file_get_contents(resource_path('views/landing/product.blade.php')));
        $this->assertStringContainsString("import './product-purchase';", file_get_contents(resource_path('js/storefront.js')));

        $script = file_get_contents(resource_path('js/product-purchase.js'));
        $this->assertStringContainsString("window.Livewire.dispatch('cart-add-requested'", $script);
        $this->assertStringContainsString("window.Livewire.dispatch('cart-quantity-requested'", $script);
        $this->assertStringContainsString("new Intl.NumberFormat('en-US'", $script);
        $this->assertStringContainsString('pendingPreviousQuantity', $script);
        $this->assertStringContainsString('window.Livewire.interceptRequest', $script);
        $this->assertStringContainsString('onError(({ response, preventDefault }) =>', $script);
        $this->assertStringContainsString('onFailure(() =>', $script);
        $this->assertStringContainsString("window.Livewire.dispatch('cart-state-requested'", $script);
        $this->assertStringContainsString('refreshSelectedQuantity()', $script);
        $this->assertStringContainsString('cart-state-requested', $script);
        $this->assertStringContainsString('x-cloak', file_get_contents(resource_path('views/landing/product.blade.php')));
        $this->assertStringContainsString('نشست شما منقضی شده است.', $script);
        $this->assertStringContainsString('destroy()', $script);

        $css = file_get_contents(resource_path('css/landing.css'));
        $this->assertStringContainsString('.product-purchase-panel { display:flex', $css);
        $this->assertStringContainsString('env(safe-area-inset-bottom,0px)', $css);
        $this->assertStringContainsString('.product-purchase-form .product-variant-option,', $css);
        $this->assertStringContainsString('scroll-margin-bottom:11rem;', $css);
    }

    public function test_product_detail_reads_customer_cart_without_creating_an_empty_cart(): void
    {
        $category = Category::create(['name' => 'خشکبار', 'slug' => 'customer-cart-read', 'is_active' => true]);
        $product = $this->product($category, 'گردوی مشتری', 'customer-cart-read', 5000000);
        $variant = $product->variants()->firstOrFail();
        $customer = Customer::create(['mobile' => '09120000085', 'mobile_verified_at' => now()]);
        $this->actingAs($customer, 'customer');

        $this->assertFalse($customer->cart()->exists());

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertViewHas('cartQuantities', fn ($quantities): bool => count((array) $quantities) === 0);

        $this->assertFalse($customer->cart()->exists());

        $this->post(route('cart.add'), [
            'variant_id' => $variant->id,
            'quantity' => 2,
        ])->assertRedirect(route('cart.index'));

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertViewHas('cartQuantities', fn ($quantities): bool => (int) ($quantities->{$variant->id} ?? 0) === 2);

        $this->assertDatabaseCount('carts', 1);
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
            ->assertSee('https://t.me/m_asadix7', false)
            ->assertDontSee('api.telegram.org')
            ->assertDontSee('bot_token')
            ->assertDontSee('تلگرام');

        $this->get('/cart')->assertOk()->assertSee('سبد خرید');
    }

    public function test_zero_weight_variants_are_excluded_from_public_catalog_and_browse_actions(): void
    {
        $category = Category::create(['name' => 'خشکبار', 'slug' => 'sellability-nuts', 'is_active' => true]);
        $visible = $this->product($category, 'گردوی قابل سفارش', 'sellable-walnuts', 13890000);
        $visible->variants()->create([
            'sku' => 'WALNUT-ZERO-WEIGHT',
            'title' => 'تنوع بدون وزن ارسال',
            'sale_quantity' => 1,
            'sale_unit' => 'kg',
            'price_irr' => 1000000,
            'shipping_weight_grams' => 0,
            'is_available' => true,
        ]);

        $hidden = $this->product($category, 'محصول بدون وزن معتبر', 'zero-weight-product', 790000);
        $hidden->variants()->update(['shipping_weight_grams' => 0]);

        $this->get('/')
            ->assertOk()
            ->assertSee('گردوی قابل سفارش')
            ->assertDontSee('محصول بدون وزن معتبر')
            ->assertDontSee('landing-product-data', false)
            ->assertDontSee('denapax-product-preview', false);

        $this->get(route('products.show', $visible->slug))
            ->assertOk()
            ->assertDontSee('تنوع بدون وزن ارسال');

        $this->get(route('products.show', $hidden->slug))->assertNotFound();

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee($visible->slug)
            ->assertDontSee($hidden->slug);

        $catalog = app(BrowseCatalog::class);
        $this->assertTrue($catalog->products()->getCollection()->contains('id', $visible->id));
        $this->assertFalse($catalog->products()->getCollection()->contains('id', $hidden->id));
        $this->assertCount(1, $catalog->product($visible->id)->variants);
        $this->assertNull($catalog->product($hidden->id));
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
