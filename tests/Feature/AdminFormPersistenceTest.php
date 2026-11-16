<?php

namespace Tests\Feature;

use App\Livewire\Admin\Catalog\CategoriesPage;
use App\Livewire\Admin\SettingsPage;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminFormPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_settings_form_persists_via_livewire(): void
    {
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin, 'web');

        $this->get(route('admin.settings'))->assertOk()
            ->assertSee('wire:submit="save"', false);

        Livewire::test(SettingsPage::class)
            ->set('storeName', 'دناپکس')
            ->set('supportContact', '09120000000')
            ->set('cardNumber', '6037991234567890')
            ->set('cardHolderName', 'مدیر دناپکس')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('store_settings', [
            'id' => 1,
            'store_name' => 'دناپکس',
            'card_number' => '6037991234567890',
        ]);
    }

    public function test_category_form_persists_via_livewire(): void
    {
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin, 'web');

        $this->get(route('admin.categories'))->assertOk()
            ->assertSee('wire:submit="save"', false);

        Livewire::test(CategoriesPage::class)
            ->set('name', 'خشکبار دنا')
            ->set('slug', 'dena-nuts')
            ->set('sortOrder', 1)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categories', [
            'name' => 'خشکبار دنا',
            'slug' => 'dena-nuts',
            'sort_order' => 1,
        ]);
    }
}
