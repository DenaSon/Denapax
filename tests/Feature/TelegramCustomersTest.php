<?php

namespace Tests\Feature;

use App\Application\Actions\AddressConversation;
use App\Application\Actions\BrowseCatalog;
use App\Application\Actions\IdentifyTelegramCustomer;
use App\Application\Actions\SaveCustomerAddress;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramCustomersTest extends TestCase
{
    use RefreshDatabase;

    public function test_telegram_identity_and_address_book_are_customer_scoped(): void
    {
        $customer = app(IdentifyTelegramCustomer::class)->execute(98765432, 98765432, 'Dena buyer');
        $this->assertSame($customer->id, app(IdentifyTelegramCustomer::class)->execute(98765432, 98765432, 'New name')->id);
        $this->assertSame('New name', $customer->fresh()->display_name);

        $action = app(SaveCustomerAddress::class);
        $first = $action->execute($customer, $this->address('A'));
        $second = $action->execute($customer, $this->address('B'));

        $this->assertTrue($first->is_default);
        $this->assertFalse($second->is_default);
        $action->setDefault($customer, $second->id);
        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_default);

        $stranger = Customer::create(['telegram_user_id' => 99999999]);
        $this->expectException(ModelNotFoundException::class);
        $action->delete($stranger, $second->id);
    }

    public function test_address_conversation_resumes_across_steps_and_persists_address(): void
    {
        $customer = Customer::create(['telegram_user_id' => 111]);
        $flow = app(AddressConversation::class);
        $action = app(SaveCustomerAddress::class);

        $this->assertStringContainsString('نام و نام خانوادگی گیرنده', $flow->start($customer));
        foreach (['Ali', '09123456789', 'Tehran', 'Tehran', 'Azadi street building 42', '1234567890'] as $input) {
            $result = $flow->accept($customer, $input, $action);
        }

        $this->assertStringContainsString('بررسی اطلاعات تحویل', $result);
        $this->assertSame('address:review', $flow->session($customer)->fresh()->state);
        $this->assertSame(0, $customer->addresses()->count());

        $this->assertStringContainsString('شماره همراه گیرنده', $flow->review($customer));
        $this->assertStringContainsString('نام و نام خانوادگی', $flow->correction($customer, 'recipient_name'));
        $this->assertStringContainsString('بررسی اطلاعات تحویل', $flow->accept($customer, 'Ali Updated', $action));
        $this->assertSame(0, $customer->addresses()->count());
        $this->assertStringContainsString('ذخیره شد', $flow->confirm($customer, $action));
        $this->assertStringContainsString('قبلاً ذخیره', $flow->confirm($customer, $action));
        $this->assertSame('idle', $flow->session($customer)->fresh()->state);
        $this->assertSame('Ali Updated', $customer->addresses()->firstOrFail()->recipient_name);
        $this->assertSame('1234567890', $customer->addresses()->firstOrFail()->postal_code);
    }


    public function test_cancel_address_form_preserves_existing_saved_address(): void
    {
        $customer = Customer::create(['telegram_user_id' => 987654]);
        $action = app(SaveCustomerAddress::class);
        $saved = $action->execute($customer, $this->address('Original'));
        $flow = app(AddressConversation::class);
        $flow->start($customer, $saved->id);
        $flow->accept($customer, 'Wrong Name', $action);
        $flow->reset($customer);
        $this->assertSame('Buyer Original', $saved->fresh()->recipient_name);
        $this->assertDatabaseCount('customer_addresses', 1);
    }

    public function test_persian_search_matches_alef_and_arabic_forms(): void
    {
        $category = Category::create(['name' => 'خشکبار', 'slug' => 'dried-fruit', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id, 'title' => 'آلبالو خشک طبیعی',
            'slug' => 'albaloo', 'summary' => 'بدون افزودنی', 'description' => 'ترش و خوش‌طعم', 'is_active' => true,
        ]);
        ProductVariant::create([
            'product_id' => $product->id, 'sku' => 'albaloo-1', 'title' => 'یک کیلو',
            'sale_quantity' => 1, 'sale_unit' => 'kg', 'price_irr' => 3500000,
            'shipping_weight_grams' => 1000, 'is_available' => true,
        ]);

        $catalog = app(BrowseCatalog::class);
        $this->assertSame($product->id, $catalog->products(term: 'البالو')->first()?->id);
        $this->assertSame($product->id, $catalog->products(term: 'آلبالو')->first()?->id);
        $this->assertSame($product->id, $catalog->products(term: 'طبيعي')->first()?->id);
        $this->assertSame(0, $catalog->products(term: 'نامرتبط')->total());
    }

    public function test_published_catalog_requires_active_category_and_available_variant(): void
    {
        $category = Category::create(['name' => 'Dena fruit', 'slug' => 'dena-fruit', 'is_active' => true]);
        $hidden = Category::create(['name' => 'Hidden', 'slug' => 'hidden', 'is_active' => false]);

        $visible = $this->product($category, 'organic-figs', true, true);
        $this->product($hidden, 'secret-figs', true, true);
        $this->product($category, 'draft-figs', false, true);
        $this->product($category, 'sold-out-figs', true, false);

        $catalog = app(BrowseCatalog::class);
        $this->assertSame(1, $catalog->products()->total());
        $this->assertSame($visible->id, $catalog->products(term: 'FIGS')->first()->id);
        $this->assertNull($catalog->product(999999));
    }

    private function product(Category $category, string $slug, bool $active, bool $available): Product
    {
        $product = Product::create([
            'category_id' => $category->id,
            'title' => $slug,
            'slug' => $slug,
            'summary' => $slug,
            'description' => 'Dena dried figs',
            'is_active' => $active,
        ]);

        ProductVariant::create([
            'product_id' => $product->id, 'sku' => $slug,
            'title' => 'One package', 'sale_quantity' => '1',
            'sale_unit' => 'piece', 'price_irr' => 190000,
            'shipping_weight_grams' => 350, 'is_available' => $available,
        ]);

        return $product;
    }

    private function address(string $suffix): array
    {
        return [
            'recipient_name' => 'Buyer '.$suffix,
            'recipient_mobile' => '09123456789',
            'province' => 'Tehran',
            'city' => 'Tehran',
            'address_line' => 'Some long street address '.$suffix,
            'postal_code' => '1234567890',
        ];
    }
}
