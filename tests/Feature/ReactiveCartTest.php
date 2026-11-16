<?php

namespace Tests\Feature;

use App\Livewire\Store\AddToCart;
use App\Livewire\Store\CartCount;
use App\Livewire\Store\CartItems;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    private function variant(): ProductVariant
    {
        $category = Category::create(['name' => 'خشکبار', 'slug' => 'nuts', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'title' => 'گردوی دنا',
            'slug' => 'dena-walnuts',
            'summary' => 'گردوی طبیعی دنا',
            'is_active' => true,
        ]);

        return $product->variants()->create([
            'sku' => 'DENA-NUTS-500',
            'title' => 'بسته ۵۰۰ گرمی',
            'sale_quantity' => 500,
            'sale_unit' => 'g',
            'price_irr' => 5000000,
            'shipping_weight_grams' => 550,
            'is_available' => true,
        ]);
    }
}
