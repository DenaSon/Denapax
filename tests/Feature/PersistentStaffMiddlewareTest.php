<?php

namespace Tests\Feature;

use App\Livewire\Admin\SettingsPage;
use App\Models\StoreSetting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;
use Tests\TestCase;

class PersistentStaffMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_deactivated_staff_cannot_save_from_an_already_open_livewire_admin_page(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        StoreSetting::create([
            'id' => 1,
            'store_name' => 'Initial store',
            'support_contact' => '09120000000',
            'card_number' => '6037991234567890',
            'card_holder_name' => 'Store Owner',
        ]);

        $this->actingAs($admin, 'web');

        $page = $this->get(route('admin.settings'))->assertOk();
        $snapshot = $this->snapshotFrom($page);

        // Simulate an administrator disabling this account while the original
        // browser tab and its authenticated session remain open.
        $admin->forceFill(['is_active' => false])->save();
        $this->actingAs($admin->fresh(), 'web');

        $response = $this->postJson(EndpointResolver::updatePath(), [
            'components' => [[
                'snapshot' => $snapshot,
                'updates' => [
                    'storeName' => 'Unauthorized change',
                    'supportContact' => '09129999999',
                    'cardNumber' => '6037999876543210',
                    'cardHolderName' => 'Unauthorized',
                ],
                'calls' => [[
                    'method' => 'save',
                    'params' => [],
                    'path' => '',
                ]],
            ]],
        ], ['X-Livewire' => 'true']);

        $response->assertForbidden();

        $this->assertDatabaseHas('store_settings', [
            'id' => 1,
            'store_name' => 'Initial store',
            'support_contact' => '09120000000',
            'card_number' => '6037991234567890',
            'card_holder_name' => 'Store Owner',
        ]);
    }

    public function test_active_authorized_staff_can_still_save_through_the_livewire_update_endpoint(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin, 'web');

        $page = $this->get(route('admin.settings'))->assertOk();
        $snapshot = $this->snapshotFrom($page);

        $response = $this->postJson(EndpointResolver::updatePath(), [
            'components' => [[
                'snapshot' => $snapshot,
                'updates' => [
                    'storeName' => 'Dena Store',
                    'supportContact' => '09120000000',
                    'cardNumber' => '6037991234567890',
                    'cardHolderName' => 'Store Owner',
                ],
                'calls' => [[
                    'method' => 'save',
                    'params' => [],
                    'path' => '',
                ]],
            ]],
        ], ['X-Livewire' => 'true']);

        $response->assertOk();

        $this->assertDatabaseHas('store_settings', [
            'id' => 1,
            'store_name' => 'Dena Store',
            'support_contact' => '09120000000',
            'card_number' => '6037991234567890',
            'card_holder_name' => 'Store Owner',
        ]);
    }

    private function snapshotFrom(TestResponse $response): string
    {
        $this->assertMatchesRegularExpression('/wire:snapshot="([^"]+)"/', $response->getContent());

        preg_match('/wire:snapshot="([^"]+)"/', $response->getContent(), $matches);

        $snapshot = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        json_decode($snapshot, true, 512, JSON_THROW_ON_ERROR);

        return $snapshot;
    }
}
