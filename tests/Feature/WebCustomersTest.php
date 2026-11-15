<?php

namespace Tests\Feature;

use App\Application\Actions\BrowseCatalog;
use App\Application\Actions\SaveCustomerAddress;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;

class WebCustomersTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_customer_addresses_are_isolated(): void
    {
        $first = Customer::create(['mobile' => '09120000021', 'mobile_verified_at' => now()]);
        $other = Customer::create(['mobile' => '09120000022', 'mobile_verified_at' => now()]);
        $action = app(SaveCustomerAddress::class);
        $address = $action->execute($first, [
            'recipient_name' => 'خریدار دنا',
            'recipient_mobile' => '09120000021',
            'province' => 'کهگیلویه و بویراحمد',
            'city' => 'سی‌سخت',
            'address_line' => 'خیابان اصلی پلاک دوازده',
            'postal_code' => '1234567890',
        ]);
        $this->assertTrue($address->is_default);

        try {
            $action->delete($other, $address->id);
            $this->fail('A customer must not delete a foreign address.');
        } catch (ModelNotFoundException) {
            $this->assertDatabaseHas('customer_addresses', ['id' => $address->id]);
        }
    }

    public function test_persian_search_still_matches_normalized_text(): void
    {
        $category = Category::create(['name' => 'میوه خشک', 'slug' => 'fruit', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id, 'title' => 'آلبالو طبیعی', 'slug' => 'albaloo',
            'summary' => 'طعم طبیعی', 'is_active' => true,
        ]);
        $product->variants()->create([
            'sku' => 'ALB-1', 'title' => 'بسته', 'sale_quantity' => 100,
            'sale_unit' => 'g', 'price_irr' => 1200000, 'shipping_weight_grams' => 120,
            'is_available' => true,
        ]);

        $this->assertSame($product->id, app(BrowseCatalog::class)->products(term: 'البالو')->first()?->id);
    }
}
