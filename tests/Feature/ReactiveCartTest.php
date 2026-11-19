<?php

namespace Tests\Feature;

use App\Application\Actions\StoreCart;
use App\Livewire\Store\AddToCart;
use App\Livewire\Store\CartCount;
use App\Livewire\Store\CartItems;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ReactiveCartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guest_cart_updates_without_redirect_and_keeps_native_form_fallback(): void
    {
        $variant = $this->variant();
        $this->post(route('cart.add'), [
            'variant_id' => $variant->id,
            'quantity' => 2,
        ])->assertRedirect(route('cart.index'));

        $this->get(route('cart.index'))->assertOk()
            ->assertSee('wire:click.prevent="changeQuantity('.$variant->id.', 3)"', false)
            ->assertSee('wire:click.prevent="remove('.$variant->id.')"', false)
            ->assertSee('action="'.route('cart.update', $variant->id).'"', false)
            ->assertSee('گردوی دنا');

        Livewire::test(CartItems::class)
            ->call('changeQuantity', $variant->id, 3)
            ->assertDispatched('cart-updated')
            ->assertSee('1,500,000')
            ->call('changeQuantity', $variant->id, 1)
            ->assertSee('500,000')
            ->call('remove', $variant->id)
            ->assertSee('سبد خرید شما خالی است.');

        $this->get(route('cart.index'))->assertOk()->assertSee('سبد خرید شما خالی است.');
    }

    public function test_customer_cart_event_refreshes_header_count_and_checks_ownership(): void
    {
        $variant = $this->variant();
        $customer = Customer::create(['mobile' => '09120000083', 'mobile_verified_at' => now()]);
        $this->actingAs($customer, 'customer');

        $this->post(route('cart.add'), [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertRedirect(route('cart.index'));

        Livewire::test(CartItems::class)->call('changeQuantity', $variant->id, 4)->assertHasNoErrors();
        Livewire::test(CartCount::class)->dispatch('cart-updated')->assertSee('تعداد اقلام سبد خرید: 4');
        $this->get(route('cart.index'))->assertOk()
            ->assertSee('ثبت سفارش')
            ->assertSee('محصولات بیشتر')
            ->assertDontSee('ورود و ثبت سفارش');

        $this->assertDatabaseHas('cart_items', ['product_variant_id' => $variant->id, 'quantity' => 4]);
        Livewire::test(CartItems::class)->call('changeQuantity', 9999999, 1)->assertHasErrors('cart');
        $this->assertDatabaseMissing('cart_items', ['product_variant_id' => 9999999]);
    }

    public function test_landing_livewire_event_adds_selected_variant_without_redirect(): void
    {
        $variant = $this->variant();

        Livewire::test(AddToCart::class)
            ->dispatch('cart-add-requested', variantId: $variant->id, quantity: 2)
            ->assertDispatched('cart-updated')
            ->assertDispatched('cart-added');

        $this->get(route('cart.index'))->assertOk()
            ->assertSee('گردوی دنا')
            ->assertSee('1,000,000');
    }

    public function test_landing_cart_event_rejects_invalid_variant_without_cart_mutation(): void
    {
        Livewire::test(AddToCart::class)
            ->dispatch('cart-add-requested', variantId: 9999999, quantity: 2)
            ->assertDispatched('cart-add-failed')
            ->assertNotDispatched('cart-added');

        $this->get(route('cart.index'))->assertOk()->assertSee('سبد خرید شما خالی است.');
    }

    public function test_product_purchase_quantity_event_updates_guest_cart_and_keeps_sellability_authoritative(): void
    {
        $variant = $this->variant();

        $this->post(route('cart.add'), [
            'variant_id' => $variant->id,
            'quantity' => 2,
        ])->assertRedirect(route('cart.index'));

        Livewire::test(AddToCart::class)
            ->dispatch('cart-quantity-requested', variantId: $variant->id, quantity: 4)
            ->assertDispatched('cart-updated')
            ->assertDispatched('cart-quantity-updated');

        $this->assertSame(4, app(StoreCart::class)->lines(null)->first()['quantity']);

        $variant->update(['is_available' => false]);

        Livewire::test(AddToCart::class)
            ->dispatch('cart-quantity-requested', variantId: $variant->id, quantity: 5)
            ->assertDispatched('cart-add-failed');

        $this->assertSame(4, app(StoreCart::class)->lines(null)->first()['quantity']);

        Livewire::test(AddToCart::class)
            ->dispatch('cart-quantity-requested', variantId: $variant->id, quantity: 0)
            ->assertDispatched('cart-quantity-updated');

        $this->assertCount(0, app(StoreCart::class)->lines(null));
    }

    public function test_product_purchase_state_refresh_returns_authoritative_cart_quantity(): void
    {
        $variant = $this->variant();

        $this->post(route('cart.add'), [
            'variant_id' => $variant->id,
            'quantity' => 2,
        ])->assertRedirect(route('cart.index'));

        Livewire::test(AddToCart::class)
            ->dispatch('cart-state-requested', variantId: $variant->id)
            ->assertDispatched('cart-updated')
            ->assertDispatched('cart-quantity-updated');

        $this->assertSame(2, app(StoreCart::class)->lines(null)->first()['quantity']);
    }

    public function test_product_purchase_quantity_event_updates_customer_cart(): void
    {
        $variant = $this->variant('customer-purchase');
        $customer = Customer::create(['mobile' => '09120000084', 'mobile_verified_at' => now()]);
        $this->actingAs($customer, 'customer');

        $this->post(route('cart.add'), [
            'variant_id' => $variant->id,
            'quantity' => 2,
        ])->assertRedirect(route('cart.index'));

        Livewire::test(AddToCart::class)
            ->dispatch('cart-quantity-requested', variantId: $variant->id, quantity: 3)
            ->assertDispatched('cart-updated')
            ->assertDispatched('cart-quantity-updated');

        $this->assertDatabaseHas('cart_items', [
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ]);
    }

    public function test_cart_uses_responsive_product_list_without_intrusive_loading_feedback(): void
    {
        $variant = $this->variant();
        $this->post(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => 2]);

        $this->get(route('cart.index'))->assertOk()
            ->assertSee('store-main-cart', false)
            ->assertSee('store-cart-grid', false)
            ->assertSee('store-cart-items-panel', false)
            ->assertSee('store-cart-summary', false)
            ->assertSee('store-cart-item-image', false)
            ->assertSee('store-cart-quantity', false)
            ->assertSee('wire:loading.attr="aria-busy"', false)
            ->assertDontSee('store-cart-loading', false)
            ->assertDontSee('در حال به‌روزرسانی سبد خرید')
            ->assertSee('store-cart-mobile-dock', false)
            ->assertSee('store-cart-mobile-dock-action', false)
            ->assertSee('ورود و ثبت سفارش')
            ->assertDontSee('ادامه به نشانی و ارسال')
            ->assertSee('تعداد کالا')
            ->assertSee('2 عدد کالا در سبد شما');

        $css = file_get_contents(resource_path('css/landing.css'));
        $this->assertStringNotContainsString('.store-cart-loading', $css);
        $this->assertStringNotContainsString('.store-cart-spinner', $css);
        $this->assertStringContainsString('.store-cart-mobile-dock {', $css);
        $this->assertStringContainsString('safe-area-inset-bottom', $css);
        $this->assertStringContainsString('.store-cart-grid { display:grid', $css);
        $this->assertStringContainsString("@source '../views/livewire/store/**/*.blade.php';", $css);
    }

    public function test_guest_cart_shows_shipping_and_livewire_refreshes_total(): void
    {
        $variant = $this->variant('cart-shipping');
        ShippingSetting::create([
            'id' => 1,
            'base_weight_grams' => 1000,
            'base_price_irr' => 900000,
            'extra_step_grams' => 500,
            'extra_step_price_irr' => 300000,
            'packaging_weight_grams' => 100,
        ]);
        $this->post(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => 1]);

        $this->get(route('cart.index'))->assertOk()
            ->assertSee('مجموع با ارسال')
            ->assertSee('90,000 تومان')
            ->assertSee('590,000')
            ->assertSee('store-cart-mobile-dock-shipping', false)
            ->assertSee('ارسال بر اساس وزن سبد محاسبه شده؛ مبلغ در مرحله ثبت سفارش بازبینی می‌شود.')
            ->assertDontSee('پس از انتخاب نشانی');

        Livewire::test(CartItems::class)
            ->call('changeQuantity', $variant->id, 2)
            ->assertHasNoErrors()
            ->assertSee('120,000 تومان')
            ->assertSee('1,120,000')
            ->call('changeQuantity', $variant->id, 3)
            ->assertHasNoErrors()
            ->assertSee('150,000 تومان')
            ->assertSee('1,650,000');

        $this->get(route('cart.index'))->assertOk()
            ->assertSee('1,650,000')
            ->assertSee('150,000 تومان');
    }

    public function test_cart_hides_shipping_total_when_tariff_missing_or_items_unavailable(): void
    {
        $variant = $this->variant('cart-shipping-unavailable');
        $this->post(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => 1]);

        $this->get(route('cart.index'))->assertOk()
            ->assertSee('فعلاً قابل محاسبه نیست')
            ->assertSee('جمع کالاها · بدون ارسال')
            ->assertDontSee('مجموع با ارسال');

        ShippingSetting::create([
            'id' => 1,
            'base_weight_grams' => 1000,
            'base_price_irr' => 900000,
            'extra_step_grams' => 500,
            'extra_step_price_irr' => 300000,
            'packaging_weight_grams' => 100,
        ]);
        $variant->update(['is_available' => false]);

        $this->get(route('cart.index'))->assertOk()
            ->assertSee('پس از اصلاح سبد')
            ->assertSee('جمع کالاها · بدون ارسال')
            ->assertDontSee('590,000')
            ->assertDontSee('مجموع با ارسال')
            ->assertSee('ابتدا کالای ناموجود را حذف کنید');
    }

    public function test_guest_cart_cta_matches_the_next_step_and_still_requires_customer_login(): void
    {
        $variant = $this->variant('cta-guest');
        $this->post(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => 1]);

        $response = $this->get(route('cart.index'))->assertOk()
            ->assertSee('ورود و ثبت سفارش')
            ->assertSee('محصولات بیشتر')
            ->assertDontSee('ادامه به نشانی و ارسال')
            ->assertSee('href="'.route('checkout.index').'"', false)
            ->assertSee('فعلاً قابل محاسبه نیست');

        // The button still targets the protected checkout so intended redirect
        // to the address step survives the OTP login flow.
        $this->get(route('checkout.index'))->assertRedirect(route('customer.login'));

        $css = file_get_contents(resource_path('css/landing.css'));
        $this->assertStringContainsString('.store-cart-summary { position:static; grid-row:2; }', $css);
        $this->assertStringContainsString('.store-cart-items-panel { grid-row:1; }', $css);
        $this->assertStringContainsString('.store-cart-summary-total { display:none; }', $css);
        $this->assertStringContainsString('.store-main-cart { padding-top:1rem; padding-bottom:0; }', $css);
    }

    public function test_cart_prefers_variant_photo_then_product_photo_and_falls_back_to_accessible_empty_state(): void
    {
        Storage::fake('public');
        $variant = $this->variant('image-priority');
        $product = $variant->product;
        $this->post(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => 1]);

        $this->get(route('cart.index'))->assertOk()
            ->assertSee('بدون تصویر');

        $productPhoto = $product->addMedia(UploadedFile::fake()->image('product.jpg'))
            ->toMediaCollection('gallery', 'public');
        $productUrl = $productPhoto->hasGeneratedConversion('product-thumb')
            ? $productPhoto->getUrl('product-thumb') : $productPhoto->getUrl();

        $this->get(route('cart.index'))->assertOk()
            ->assertSee('src="'.$productUrl.'"', false)
            ->assertDontSee('بدون تصویر');

        $variantPhoto = $variant->addMedia(UploadedFile::fake()->image('specific-variant.jpg'))
            ->toMediaCollection('variant-image', 'public');
        $variantUrl = $variantPhoto->hasGeneratedConversion('product-thumb')
            ? $variantPhoto->getUrl('product-thumb') : $variantPhoto->getUrl();

        $this->get(route('cart.index'))->assertOk()
            ->assertSee('src="'.$variantUrl.'"', false)
            ->assertDontSee('src="'.$productUrl.'"', false)
            ->assertSee('alt="گردوی دنا - بسته ۵۰۰ گرمی"', false);
    }

    public function test_mobile_cart_dock_disables_checkout_when_a_cart_line_is_unavailable(): void
    {
        $variant = $this->variant('mobile-dock-unavailable');
        $this->post(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => 2])
            ->assertRedirect(route('cart.index'));
        $variant->update(['is_available' => false]);

        $this->get(route('cart.index'))->assertOk()
            ->assertSee('store-cart-mobile-dock', false)
            ->assertSee('class="store-cart-mobile-dock-action" disabled', false)
            ->assertSee('ابتدا کالای ناموجود را حذف کنید');
    }

    public function test_guest_cart_rejects_increases_to_unsellable_lines_but_allows_decrease_and_removal(): void
    {
        $variant = $this->variant();
        $this->post(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => 2])
            ->assertRedirect(route('cart.index'));

        $variant->update(['is_available' => false]);

        Livewire::test(CartItems::class)
            ->call('changeQuantity', $variant->id, 3)
            ->assertHasErrors('cart');

        $this->assertSame(2, session('denapax_cart')[$variant->id]);

        $this->patch(route('cart.update', $variant->id), ['quantity' => 3])
            ->assertSessionHasErrors('cart');

        $this->assertSame(2, session('denapax_cart')[$variant->id]);

        Livewire::test(CartItems::class)
            ->call('changeQuantity', $variant->id, 1)
            ->assertHasNoErrors();

        $this->assertSame(1, session('denapax_cart')[$variant->id]);

        $this->patch(route('cart.update', $variant->id), ['quantity' => 0])
            ->assertRedirect(route('cart.index'));

        $this->assertArrayNotHasKey($variant->id, session('denapax_cart', []));
    }

    public function test_customer_cart_rejects_increases_to_unsellable_lines_but_allows_decrease_and_removal(): void
    {
        $variant = $this->variant();
        $customer = Customer::create(['mobile' => '09120000084', 'mobile_verified_at' => now()]);
        $this->actingAs($customer, 'customer');

        $this->post(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => 2])
            ->assertRedirect(route('cart.index'));

        $variant->update(['is_available' => false]);

        Livewire::test(CartItems::class)
            ->call('changeQuantity', $variant->id, 3)
            ->assertHasErrors('cart');

        $this->assertDatabaseHas('cart_items', ['product_variant_id' => $variant->id, 'quantity' => 2]);

        $this->patch(route('cart.update', $variant->id), ['quantity' => 3])
            ->assertSessionHasErrors('cart');

        $this->assertDatabaseHas('cart_items', ['product_variant_id' => $variant->id, 'quantity' => 2]);

        Livewire::test(CartItems::class)
            ->call('changeQuantity', $variant->id, 1)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('cart_items', ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->patch(route('cart.update', $variant->id), ['quantity' => 0])
            ->assertRedirect(route('cart.index'));

        $this->assertDatabaseMissing('cart_items', ['product_variant_id' => $variant->id]);
    }

    public function test_shared_sellability_rule_rejects_invalid_variant_and_unpublished_parent_for_both_cart_types(): void
    {
        $customer = Customer::create(['mobile' => '09120000085', 'mobile_verified_at' => now()]);
        $cart = app(StoreCart::class);
        $invalidators = [
            'unavailable' => fn (ProductVariant $variant) => $variant->update(['is_available' => false]),
            'zero-price' => fn (ProductVariant $variant) => $variant->update(['price_irr' => 0]),
            'zero-weight' => fn (ProductVariant $variant) => $variant->update(['shipping_weight_grams' => 0]),
            'inactive-product' => fn (ProductVariant $variant) => $variant->product->update(['is_active' => false]),
            'inactive-category' => fn (ProductVariant $variant) => $variant->product->category->update(['is_active' => false]),
        ];

        foreach ($invalidators as $case => $invalidate) {
            $variant = $this->variant($case);
            $invalidate($variant);
            $variant = $variant->fresh()->load('product.category');

            $this->assertFalse($variant->isSellable(), $case);
            $this->assertFalse(ProductVariant::query()->sellable()->whereKey($variant->id)->exists(), $case);
            $this->assertFalse(Product::query()->publiclyOrderable()->whereKey($variant->product_id)->exists(), $case);

            try {
                $cart->add(null, $variant->id, 1);
                $this->fail("Guest cart accepted {$case} variant.");
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('cart', $exception->errors());
            }

            try {
                $cart->add($customer, $variant->id, 1);
                $this->fail("Customer cart accepted {$case} variant.");
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('cart', $exception->errors());
            }
        }
    }

    private function variant(string $suffix = ''): ProductVariant
    {
        $categorySlug = $suffix === '' ? 'nuts' : 'nuts-'.$suffix;
        $productSlug = $suffix === '' ? 'dena-walnuts' : 'dena-walnuts-'.$suffix;
        $sku = $suffix === '' ? 'DENA-NUTS-500' : 'DENA-NUTS-500-'.$suffix;
        $category = Category::create(['name' => 'خشکبار', 'slug' => $categorySlug, 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'title' => 'گردوی دنا',
            'slug' => $productSlug,
            'summary' => 'گردوی طبیعی دنا',
            'is_active' => true,
        ]);

        return $product->variants()->create([
            'sku' => $sku,
            'title' => 'بسته ۵۰۰ گرمی',
            'sale_quantity' => 500,
            'sale_unit' => 'g',
            'price_irr' => 5000000,
            'shipping_weight_grams' => 550,
            'is_available' => true,
        ]);
    }
}
