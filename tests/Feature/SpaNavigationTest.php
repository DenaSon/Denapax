<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_public_pages_mount_shared_offline_indicator_and_navigation(): void
    {
        $this->get('/')->assertOk()->assertSee('wire:offline', false)
            ->assertSee('wire:navigate', false);
        $this->get(route('cart.index'))->assertOk()->assertSee('wire:offline', false)
            ->assertSee('wire:navigate', false);

        $this->assertStringContainsString("import './navigation';", file_get_contents(resource_path('js/storefront.js')));
        $this->assertStringContainsString('livewire:navigated', file_get_contents(resource_path('js/navigation.js')));
        $this->assertStringContainsString('wire:cloak', file_get_contents(resource_path('views/livewire/store/network-status.blade.php')));
    }

    public function test_staff_internal_links_navigate_but_private_receipt_images_do_not(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin, 'web');

        $this->get(route('admin.dashboard'))->assertOk()
            ->assertSee('wire:navigate', false)
            ->assertSee('href="'.route('admin.orders').'"', false);

        $nav = file_get_contents(resource_path('views/admin/partials/navigation.blade.php'));
        $this->assertStringContainsString('<a wire:navigate class="admin-nav-link', $nav);

        $order = file_get_contents(resource_path('views/livewire/admin/orders/order-detail-page.blade.php'));
        $this->assertStringContainsString('target="_blank"', $order);
        $this->assertStringNotContainsString('wire:navigate href="{{ route(\'admin.payments.receipt.image\'', $order);
    }

    public function test_financial_confirmation_remains_server_post_instead_of_optimistic_browser_action(): void
    {
        $view = file_get_contents(resource_path('views/store/checkout/index.blade.php'));
        $this->assertStringContainsString('method="POST" action="{{ route(\'checkout.confirm\') }}"', $view);
        $this->assertStringContainsString('data-submit-once', $view);

        $receipt = file_get_contents(resource_path('views/store/checkout/confirmation.blade.php'));
        $this->assertStringContainsString('enctype="multipart/form-data"', $receipt);
        $this->assertStringContainsString('orders.receipts.store', $receipt);
    }
}
