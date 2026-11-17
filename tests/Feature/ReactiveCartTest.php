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
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_cart_uses_responsive_product_list_and_fixed_loading_feedback(): void
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
            ->assertSee('store-cart-loading', false)
            ->assertSee('store-cart-mobile-dock', false)
            ->assertSee('store-cart-mobile-dock-action', false)
            ->assertSee('تعداد کالا')
            ->assertSee('2 عدد کالا در سبد شما');

        $css = file_get_contents(resource_path('css/landing.css'));
        $this->assertStringContainsString('.store-cart-loading { position:fixed', $css);
        $this->assertStringContainsString('.store-cart-mobile-dock {', $css);
        $this->assertStringContainsString('safe-area-inset-bottom', $css);
        $this->assertStringContainsString('.store-cart-grid { display:grid', $css);
        $this->assertStringContainsString("@source '../views/livewire/store/**/*.blade.php';", $css);
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
