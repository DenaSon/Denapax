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

        $savedResponse = $this->post(route('addresses.store'), [
            ...$this->addressData(),
            'recipient_mobile' => '۰۹۱۲۳۴۵۶۷۸۹',
            'postal_code' => '۱۲۳۴۵۶۷۸۹۰',
            'return' => 'checkout',
        ]);

        $address = CustomerAddress::query()->sole();
        $savedResponse->assertRedirect(route('checkout.index', ['address_id' => $address->id]));
        $this->assertSame('09123456789', $address->recipient_mobile);
        $this->assertSame('1234567890', $address->postal_code);
        $this->get(route('checkout.index'))->assertOk()->assertSee('آلو بخارا دنا');

        $this->post(route('checkout.preview'), ['address_id' => $address->id])
            ->assertRedirect(route('checkout.review'));
        $this->get(route('checkout.review'))->assertOk()
            ->assertSee('150,000')->assertSee('450,000')
            ->assertSee('1,050')->assertSee('وزن ارسالی');

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

    public function test_inline_checkout_shows_shipping_and_confirms_in_one_step(): void
    {
        $this->actingAs($this->customer, 'customer');
        $address = $this->makeAddress();
        $this->post(route('cart.add'), ['variant_id' => $this->variant->id, 'quantity' => 2]);

        $this->get(route('checkout.index'))->assertOk()
            ->assertSee('مبلغ نهایی')
            ->assertSee('450,000')
            ->assertSee('150,000')
            ->assertSee('ثبت سفارش و ادامه به پرداخت')
            ->assertDontSee('محاسبه ارسال و بررسی نهایی');

        $context = session('web_checkout_preview');
        $this->assertSame($address->id, $context['address_id']);
        $firstKey = $context['checkout_key'];

        // Refreshing the same quote must not change its idempotency key.
        $this->get(route('checkout.index'))->assertOk();
        $this->assertSame($firstKey, session('web_checkout_preview.checkout_key'));

        $this->post(route('checkout.confirm'), ['checkout_key' => $firstKey])->assertRedirect();
        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(4500000, Order::query()->sole()->total_irr);
        $this->get(route('orders.confirmation', Order::query()->sole()->order_number))
            ->assertOk()->assertSee('تکمیل پرداخت');
    }

    public function test_switching_to_another_address_invalidates_previous_checkout_key(): void
    {
        $this->actingAs($this->customer, 'customer');
        $first = $this->makeAddress();
        $second = $this->customer->addresses()->create([
            ...$this->addressData(), 'label' => 'محل کار', 'is_default' => false,
        ]);
        $this->post(route('cart.add'), ['variant_id' => $this->variant->id, 'quantity' => 1]);

        $this->get(route('checkout.index'))->assertOk()->assertSee('خانه');
        $firstKey = session('web_checkout_preview.checkout_key');
        $this->assertSame($first->id, session('web_checkout_preview.address_id'));

        $this->get(route('checkout.index', ['address_id' => $second->id]))->assertOk()
            ->assertSee('محل کار');
        $this->assertSame($second->id, session('web_checkout_preview.address_id'));
        $newKey = session('web_checkout_preview.checkout_key');
        $this->assertNotSame($firstKey, $newKey);

        $this->post(route('checkout.confirm'), ['checkout_key' => $firstKey])
            ->assertRedirect(route('checkout.index'))
            ->assertSessionHasErrors('checkout');
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(1, $this->customer->cart->items()->count());

        $this->get(route('checkout.index', ['address_id' => 999999]))->assertNotFound();
        $another = Customer::create(['mobile' => '09120000888', 'mobile_verified_at' => now()]);
        $otherAddress = $another->addresses()->create($this->addressData());
        $this->get(route('checkout.index', ['address_id' => $otherAddress->id]))->assertNotFound();
    }

    public function test_price_change_regenerates_quote_and_does_not_confirm_stale_checkout(): void
    {
        $this->actingAs($this->customer, 'customer');
        $this->makeAddress();
        $this->post(route('cart.add'), ['variant_id' => $this->variant->id, 'quantity' => 1]);
        $this->get(route('checkout.index'))->assertOk();
        $staleKey = session('web_checkout_preview.checkout_key');

        $this->variant->update(['price_irr' => 2200000]);
        $this->get(route('checkout.index'))->assertOk()->assertSee('220,000');
        $this->assertNotSame($staleKey, session('web_checkout_preview.checkout_key'));
        $this->post(route('checkout.confirm'), ['checkout_key' => $staleKey])
            ->assertRedirect(route('checkout.index'))->assertSessionHasErrors('checkout');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_cart_quantity_buttons_work_for_guest_and_customer(): void
    {
        $this->post(route('cart.add'), ['variant_id' => $this->variant->id, 'quantity' => 2]);
        $this->get(route('cart.index'))->assertOk()
            ->assertSee('کم کردن تعداد')->assertSee('زیاد کردن تعداد');

        $this->patch(route('cart.update', $this->variant->id), ['quantity' => 1])
            ->assertRedirect(route('cart.index'));
        $this->get(route('cart.index'))->assertSee('150,000');

        $this->patch(route('cart.update', $this->variant->id), ['quantity' => 0])
            ->assertRedirect(route('cart.index'));
        $this->get(route('cart.index'))->assertSee('سبد خرید شما خالی است');

        $this->actingAs($this->customer, 'customer')
            ->post(route('cart.add'), ['variant_id' => $this->variant->id, 'quantity' => 1]);
        $this->patch(route('cart.update', $this->variant->id), ['quantity' => 2])
            ->assertRedirect(route('cart.index'));
        $this->assertSame(2, $this->customer->cart->items()->sole()->quantity);
    }

    public function test_polished_checkout_renders_one_primary_action_and_progress_steps(): void
    {
        $this->actingAs($this->customer, 'customer');
        $this->makeAddress();
        $this->post(route('cart.add'), ['variant_id' => $this->variant->id, 'quantity' => 1]);

        $this->get(route('checkout.index'))->assertOk()
            ->assertSee('مراحل خرید')
            ->assertSee('aria-current="step"', false)
            ->assertSee('store-checkout-total')
            ->assertSee('data-submit-once')
            ->assertSee('در حال ثبت سفارش...')
            ->assertSee('Vazirmatn');

        $this->assertStringContainsString(
            "@source '../views/store/**/*.blade.php';",
            file_get_contents(resource_path('css/landing.css')),
        );
        $this->assertStringContainsString(
            "@source '../views/layouts/store.blade.php';",
            file_get_contents(resource_path('css/landing.css')),
        );
    }

    public function test_customer_storefront_assets_are_registered_in_production_vite_inputs(): void
    {
        // Feature tests normally bypass Vite; a missing JS entry can therefore
        // silently pass CI while /cart throws a 500 against a real manifest.
        $layout = file_get_contents(resource_path('views/layouts/store.blade.php'));
        $config = file_get_contents(base_path('vite.config.js'));

        foreach (['resources/css/landing.css', 'resources/js/store.js'] as $entry) {
            $this->assertStringContainsString("'".$entry."'", $layout);
            $this->assertStringContainsString("'".$entry."'", $config);
        }
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
