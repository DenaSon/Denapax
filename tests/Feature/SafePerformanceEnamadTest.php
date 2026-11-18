<?php

namespace Tests\Feature;

use App\Application\Actions\StoreCart;
use App\Livewire\Store\LandingHeaderCart;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SafePerformanceEnamadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_enamad_meta_and_exact_empty_public_verification_file_exist(): void
    {
        $this->assertFileExists(public_path('45504479.txt'));
        $this->assertSame('', file_get_contents(public_path('45504479.txt')));
        $this->get(route('home'))->assertOk()
            ->assertSee('<meta name="enamad" content="45504479" />', false);
    }

    public function test_homepage_uses_one_shared_reactive_cart_component_with_two_responsive_links(): void
    {
        $markup = file_get_contents(resource_path('views/landing/index.blade.php'));
        $this->assertSame(1, substr_count($markup, '<livewire:store.landing-header-cart />'));
        $this->assertStringNotContainsString('<livewire:store.cart-count />', $markup);

        $this->withSession(['denapax_cart' => [101 => 2, 202 => 1]]);
        Livewire::test(LandingHeaderCart::class)
            ->assertSee('landing-mobile-cart', false)
            ->assertSee('wire:navigate.hover', false)
            ->assertSee('تعداد اقلام سبد خرید: 3', false);
    }

    public function test_targeted_variant_counts_and_single_cart_total_for_guests(): void
    {
        $this->withSession(['denapax_cart' => [101 => 2, 202 => 5]]);
        $cart = app(StoreCart::class);

        $this->assertSame([101 => 2], $cart->quantitiesForVariants(null, [101, 303]));
        $this->assertSame([], $cart->quantitiesForVariants(null, []));
        $this->assertSame(7, $cart->quantity(null));
    }

    public function test_customer_without_cart_gets_zero_without_creating_a_cart(): void
    {
        $customer = Customer::create(['mobile' => '09120000077', 'mobile_verified_at' => now()]);

        $this->assertSame(0, app(StoreCart::class)->quantity($customer));
        $this->assertSame([], app(StoreCart::class)->quantitiesForVariants($customer, [101, 202]));
        $this->assertFalse($customer->cart()->exists());
    }

    public function test_limited_prefetch_and_index_friendly_date_filter_sources(): void
    {
        $card = file_get_contents(resource_path('views/landing/partials/product-card.blade.php'));
        $this->assertStringContainsString('wire:navigate.hover', $card);

        $orders = file_get_contents(app_path('Livewire/Admin/Orders/OrdersPage.php'));
        $this->assertStringNotContainsString('whereDate(', $orders);
        $this->assertStringContainsString("CarbonImmutable::parse(\$filters['dateTo'])->addDay()", $orders);
    }
}
