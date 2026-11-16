<?php

namespace Tests\Feature;

use App\Livewire\Store\AddressEditor;
use App\Livewire\Store\AddressList;
use App\Livewire\Store\AddressPicker;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReactiveAddressTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->customer = Customer::create(['mobile' => '09120000085', 'mobile_verified_at' => now()]);
        $this->actingAs($this->customer, 'customer');
    }

    public function test_address_editor_saves_with_livewire_and_preserves_native_post_form(): void
    {
        $this->get(route('addresses.create'))->assertOk()
            ->assertSee('wire:submit="save"', false)
            ->assertSee('action="'.route('addresses.store').'"', false);

        Livewire::test(AddressEditor::class, ['returnTo' => 'addresses'])
            ->set('recipient_name', 'خریدار دنا')
            ->set('recipient_mobile', '۰۹۱۲۰۰۰۰۰۸۵')
            ->set('province', 'کهگیلویه و بویراحمد')
            ->set('city', 'سی‌سخت')
            ->set('address_line', 'خیابان اصلی، کوچه گلستان، پلاک دوازده')
            ->set('postal_code', '۱۲۳۴۵۶۷۸۹۰')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('addresses.index'));

        $this->assertDatabaseHas('customer_addresses', [
            'customer_id' => $this->customer->id,
            'recipient_mobile' => '09120000085',
            'postal_code' => '1234567890',
            'is_default' => true,
        ]);
    }

    public function test_checkout_address_selection_uses_navigation_redirect_and_enforces_owner(): void
    {
        $first = $this->address('خانه', true);
        $second = $this->address('دفتر', false);

        Livewire::test(AddressPicker::class, ['selectedAddressId' => $first->id])
            ->call('choose', $second->id)
            ->assertRedirect(route('checkout.index', ['address_id' => $second->id]));

        $this->assertDatabaseHas('customer_addresses', ['id' => $first->id, 'is_default' => true]);
        $this->assertDatabaseHas('customer_addresses', ['id' => $second->id, 'is_default' => false]);

        $this->assertStringContainsString(
            'wire:click.prevent="choose(',
            file_get_contents(resource_path('views/livewire/store/address-picker.blade.php')),
        );
    }

    public function test_address_list_updates_default_and_deletes_without_full_reload(): void
    {
        $first = $this->address('خانه', true);
        $second = $this->address('دفتر', false);

        $this->get(route('addresses.index'))->assertOk()
            ->assertSee('wire:click.prevent="makeDefault(', false)
            ->assertSee('wire:click.prevent="delete(', false);

        Livewire::test(AddressList::class)
            ->call('makeDefault', $second->id)
            ->assertHasNoErrors()
            ->assertSee('نشانی پیش‌فرض تغییر کرد.')
            ->call('delete', $first->id)
            ->assertHasNoErrors()
            ->assertSee('نشانی حذف شد.');

        $this->assertDatabaseMissing('customer_addresses', ['id' => $first->id]);
        $this->assertDatabaseHas('customer_addresses', ['id' => $second->id, 'is_default' => true]);
    }

    private function address(string $label, bool $default): CustomerAddress
    {
        return $this->customer->addresses()->create([
            'label' => $label,
            'recipient_name' => 'خریدار دنا',
            'recipient_mobile' => '09120000085',
            'province' => 'کهگیلویه و بویراحمد',
            'city' => 'سی‌سخت',
            'address_line' => 'خیابان اصلی، کوچه گلستان، پلاک دوازده',
            'postal_code' => '1234567890',
            'is_default' => $default,
        ]);
    }
}
