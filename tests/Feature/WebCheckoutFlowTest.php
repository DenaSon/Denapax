<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingSetting;
use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebCheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->customer = Customer::create(['mobile' => '09120000041', 'mobile_verified_at' => now()]);
        $category = Category::create(['name' => 'خشکبار', 'slug' => 'dry-fruit', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'title' => 'آلو بخارا دنا',
            'slug' => 'dena-prunes',
            'summary' => 'آلو بخارا طبیعی سی سخت',
            'is_active' => true,
        ]);
        $this->variant = $product->variants()->create([
            'sku' => 'PRUNE-500', 'title' => 'بسته ۵۰۰ گرمی',
            'sale_quantity' => 500, 'sale_unit' => 'g',
            'price_irr' => 1500000, 'shipping_weight_grams' => 450,
            'is_available' => true,
        ]);

        ShippingSetting::create([
            'id' => 1, 'base_weight_grams' => 1000,
            'base_price_irr' => 1000000, 'extra_step_grams' => 1000,
            'extra_step_price_irr' => 500000, 'packaging_weight_grams' => 150,
        ]);
        StoreSetting::create([
            'id' => 1, 'store_name' => 'Dena',
            'support_contact' => null,
            'card_number' => '6037991234567890',
            'card_holder_name' => 'Dena Owner',
        ]);
    }

    public function test_guest_is_sent_to_customer_login_for_checkout(): void
    {
        $this->post(route('cart.add'), ['variant_id' => $this->variant->id, 'quantity' => 1])
            ->assertRedirect(route('cart.index'));
        $this->get(route('checkout.index'))->assertRedirect(route('customer.login'));
    }

    public function test_web_checkout_uses_verified_customer_address_and_is_idempotent(): void
    {
        $this->actingAs($this->customer, 'customer');
        $this->post(route('cart.add'), ['variant_id' => $this->variant->id, 'quantity' => 2])
            ->assertRedirect(route('cart.index'));

        $this->post(route('addresses.store'), [
            ...$this->addressData(),
            'recipient_mobile' => '۰۹۱۲۳۴۵۶۷۸۹',
            'postal_code' => '۱۲۳۴۵۶۷۸۹۰',
            'return' => 'checkout',
        ])->assertRedirect(route('checkout.index'));

        $address = CustomerAddress::query()->sole();
        $this->assertSame('09123456789', $address->recipient_mobile);
        $this->assertSame('1234567890', $address->postal_code);
        $this->get(route('checkout.index'))->assertOk()->assertSee('آلو بخارا دنا');

        $this->post(route('checkout.preview'), ['address_id' => $address->id])
            ->assertRedirect(route('checkout.review'));
        $this->get(route('checkout.review'))->assertOk()
            ->assertSee('150,000')->assertSee('450,000')
            ->assertSee('1,050')->assertSee('محاسبه');

        $context = session('web_checkout_preview');
        $this->assertSame($this->customer->id, $context['customer_id']);

        $this->post(route('checkout.confirm'), ['checkout_key' => $context['checkout_key']])
            ->assertRedirect();
        $order = Order::query()->sole();
        $this->assertSame(4500000, $order->total_irr);
        $this->assertSame(1500000, $order->shipping_fee_irr);
        $this->assertSame('awaiting_payment', $order->order_status);
        $this->assertSame(2, $order->items()->sole()->quantity);
        $this->assertSame(0, $this->customer->cart->items()->count());

        $this->post(route('checkout.confirm'), ['checkout_key' => $context['checkout_key']])
            ->assertRedirect(route('orders.confirmation', $order->order_number));
        $this->get(route('orders.confirmation', $order->order_number))
            ->assertOk()->assertSee($order->public_reference)->assertSee('450,000');

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('outbox_events', 1);
    }

    public function test_stale_quote_does_not_create_order_and_preserves_cart(): void
    {
        $this->actingAs($this->customer, 'customer');
        $address = $this->makeAddress();
        $this->post(route('cart.add'), ['variant_id' => $this->variant->id, 'quantity' => 1]);

        $this->post(route('checkout.preview'), ['address_id' => $address->id])
            ->assertRedirect(route('checkout.review'));
        $context = session('web_checkout_preview');

        $this->variant->update(['price_irr' => 1900000]);
        $this->get(route('checkout.review'))->assertRedirect(route('checkout.index'))
            ->assertSessionHasErrors('checkout');
        $this->post(route('checkout.confirm'), ['checkout_key' => $context['checkout_key']])
            ->assertRedirect(route('checkout.index'));

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(1, $this->customer->cart->items()->sole()->quantity);
    }

    public function test_addresses_and_order_confirmation_are_isolated_by_customer(): void
    {
        $this->actingAs($this->customer, 'customer');
        $address = $this->makeAddress();
        $this->post(route('cart.add'), ['variant_id' => $this->variant->id, 'quantity' => 1]);
        $this->post(route('checkout.preview'), ['address_id' => $address->id]);
        $context = session('web_checkout_preview');
        $this->post(route('checkout.confirm'), ['checkout_key' => $context['checkout_key']]);
        $order = Order::query()->sole();

        $other = Customer::create(['mobile' => '09120000042', 'mobile_verified_at' => now()]);
        $this->actingAs($other, 'customer');

        $this->get(route('addresses.edit', $address->id))->assertNotFound();
        $this->put(route('addresses.update', $address->id), $this->addressData())->assertNotFound();
        $this->post(route('addresses.default', $address->id))->assertNotFound();
        $this->delete(route('addresses.destroy', $address->id))->assertNotFound();
        $this->post(route('checkout.preview'), ['address_id' => $address->id])
            ->assertSessionHasErrors('address');
        $this->get(route('orders.confirmation', $order->order_number))->assertNotFound();
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_web_search_matches_persian_variants_without_exposing_unrelated_products(): void
    {
        $category = Category::query()->firstOrFail();
        $other = Product::create([
            'category_id' => $category->id, 'title' => 'مغز گردو دنا',
            'slug' => 'walnut-dena', 'summary' => 'گردوی سی سخت', 'is_active' => true,
        ]);
        $other->variants()->create([
            'sku' => 'WALNUT-100', 'title' => 'بسته', 'sale_quantity' => 100,
            'sale_unit' => 'g', 'price_irr' => 900000, 'shipping_weight_grams' => 150,
            'is_available' => true,
        ]);

        $this->get(route('home', ['q' => 'الو']))->assertOk()
            ->assertSee('آلو بخارا دنا')->assertDontSee('مغز گردو دنا');
        $this->get(route('home', ['q' => 'گردو']))->assertOk()
            ->assertSee('مغز گردو دنا')->assertDontSee('آلو بخارا دنا');
    }

    private function makeAddress(): CustomerAddress
    {
        return $this->customer->addresses()->create([
            ...$this->addressData(),
            'is_default' => true,
        ]);
    }

    private function addressData(): array
    {
        return [
            'label' => 'خانه',
            'recipient_name' => 'خریدار دنا',
            'recipient_mobile' => '09123456789',
            'province' => 'کهگیلویه و بویراحمد',
            'city' => 'سی سخت',
            'address_line' => 'خیابان اصلی کوچه دوم پلاک هفت',
            'postal_code' => '1234567890',
        ];
    }
}
