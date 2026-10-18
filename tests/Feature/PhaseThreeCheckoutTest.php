<?php

namespace Tests\Feature;

use App\Application\Actions\CreateOrder;
use App\Application\Actions\ManageCart;
use App\Application\Actions\PrepareCheckout;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingSetting;
use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PhaseThreeCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private CustomerAddress $address;

    private ProductVariant $first;

    private ProductVariant $second;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create(['telegram_user_id' => 3001]);
        $this->address = $this->customer->addresses()->create([
            'recipient_name' => 'Dena Buyer',
            'recipient_mobile' => '09123456789',
            'province' => 'Tehran',
            'city' => 'Tehran',
            'address_line' => 'Long residential street 24',
            'postal_code' => '1234567890',
            'is_default' => true,
        ]);

        $category = Category::create(['name' => 'Dry fruits', 'slug' => 'dry-fruits', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id, 'title' => 'Dena figs',
            'slug' => 'dena-figs', 'summary' => 'Dried figs', 'is_active' => true,
        ]);
        $this->first = $product->variants()->create([
            'sku' => 'FIG500', 'title' => '500 grams', 'sale_quantity' => 500,
            'sale_unit' => 'g', 'price_irr' => 1200000,
            'shipping_weight_grams' => 450, 'is_available' => true,
        ]);
        $this->second = $product->variants()->create([
            'sku' => 'FIG200', 'title' => '200 grams', 'sale_quantity' => 200,
            'sale_unit' => 'g', 'price_irr' => 500000,
            'shipping_weight_grams' => 450, 'is_available' => true,
        ]);

        ShippingSetting::create([
            'id' => 1, 'base_weight_grams' => 1000, 'base_price_irr' => 1000000,
            'extra_step_grams' => 1000, 'extra_step_price_irr' => 500000,
            'packaging_weight_grams' => 150,
        ]);
        StoreSetting::create([
            'id' => 1, 'store_name' => 'Dena', 'support_contact' => '@dena',
            'card_number' => '6037991234567890', 'card_holder_name' => 'Dena Owner',
        ]);
    }

    public function test_cart_checkout_snapshots_two_items_and_deduplicates_confirmations(): void
    {
        $cart = app(ManageCart::class);
        $cart->add($this->customer, $this->first->id);
        $cart->add($this->customer, $this->second->id);
        $cart->add($this->customer, $this->first->id);
        $cart->setQuantity($this->customer, $this->first->id, 1);

        $quote = app(PrepareCheckout::class)->execute($this->customer, $this->address->id);
        $this->assertSame(1050, $quote['shipping_weight_grams']);
        $this->assertSame(1700000, $quote['goods_subtotal_irr']);
        $this->assertSame(1500000, $quote['shipping_fee_irr']);
        $this->assertSame(3200000, $quote['total_irr']);

        $key = (string) Str::uuid();
        $create = app(CreateOrder::class);
        $order = $create->execute($this->customer, $this->address->id, $key, app(PrepareCheckout::class)->fingerprint($quote));
        $again = $create->execute($this->customer, $this->address->id, $key, app(PrepareCheckout::class)->fingerprint($quote));
        $this->assertSame($order->id, $again->id);
        $this->assertSame('awaiting_payment', $order->order_status);
        $this->assertSame('awaiting_receipt', $order->payment_status);
        $this->assertSame(2, $order->items()->count());
        $this->assertSame(0, $this->customer->cart->items()->count());
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('outbox_events', 1);
        $this->assertDatabaseHas('order_status_histories', ['order_id' => $order->id, 'to_status' => 'awaiting_payment']);

        $this->first->update(['price_irr' => 9000000, 'shipping_weight_grams' => 3000]);
        $this->address->update(['address_line' => 'Changed to another street']);
        ShippingSetting::findOrFail(1)->update(['base_price_irr' => 9000000]);
        StoreSetting::findOrFail(1)->update(['card_number' => '1111222233334444']);
        $this->assertSame(3200000, $order->fresh()->total_irr);
        $this->assertSame('Long residential street 24', $order->fresh()->address_line);
        $this->assertSame('6037991234567890', $order->fresh()->payment_instructions_snapshot['card_number']);
        $this->assertSame(1200000, $order->items()->where('product_variant_id', $this->first->id)->firstOrFail()->unit_price_irr);
        $this->assertSame(1000000, $order->shipping_rule_snapshot['base_price_irr']);
    }

    public function test_changed_price_prevents_confirmation_and_leaves_cart_intact(): void
    {
        app(ManageCart::class)->add($this->customer, $this->first->id);
        $quote = app(PrepareCheckout::class)->execute($this->customer, $this->address->id);
        $this->first->update(['price_irr' => 9999999]);

        try {
            app(CreateOrder::class)->execute(
                $this->customer,
                $this->address->id,
                (string) Str::uuid(),
                app(PrepareCheckout::class)->fingerprint($quote),
            );
            $this->fail('Checkout must reject a stale preview.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('orders', 0);
            $this->assertSame(1, $this->customer->cart->items()->count());
        }
    }

    public function test_customer_cannot_checkout_another_customers_address_or_unavailable_product(): void
    {
        $other = Customer::create(['telegram_user_id' => 55555]);
        app(ManageCart::class)->add($other, $this->first->id);

        $this->expectException(ValidationException::class);
        app(PrepareCheckout::class)->execute($other, $this->address->id);
    }

    public function test_inactive_variants_are_not_added_to_cart(): void
    {
        $this->second->update(['is_available' => false]);

        $this->expectException(ValidationException::class);
        app(ManageCart::class)->add($this->customer, $this->second->id);
    }
}
