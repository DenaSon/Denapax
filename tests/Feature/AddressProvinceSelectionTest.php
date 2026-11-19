<?php

namespace Tests\Feature;

use App\Application\Actions\SaveCustomerAddress;
use App\Livewire\Store\AddressEditor;
use App\Models\Customer;
use App\Support\IranProvinces;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class AddressProvinceSelectionTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->customer = Customer::create([
            'mobile' => '09120000081',
            'mobile_verified_at' => now(),
        ]);
        $this->actingAs($this->customer, 'customer');
    }

    public function test_province_catalog_is_unique_complete_and_normalizes_legacy_letters_and_spacing(): void
    {
        $provinces = IranProvinces::all();

        $this->assertCount(31, $provinces);
        $this->assertCount(31, array_unique($provinces));
        $this->assertContains('کهگیلویه و بویراحمد', $provinces);
        $this->assertContains('سیستان و بلوچستان', $provinces);
        $this->assertContains('تهران', $provinces);
        $this->assertSame('آذربایجان غربی', IranProvinces::normalize(' آذربايجان   غربي '));
        $this->assertSame('کهگیلویه و بویراحمد', IranProvinces::normalize("كهگيلويه\u{200C}و بويراحمد"));
        $this->assertTrue(IranProvinces::contains('كهگيلويه و بويراحمد'));
        $this->assertFalse(IranProvinces::contains('استان ساختگی'));
    }

    public function test_native_and_livewire_address_editor_show_the_same_province_list_and_manual_city_input(): void
    {
        $html = $this->get(route('addresses.create'))->assertOk()
            ->assertSee('wire:submit="save"', false)
            ->assertSee('action="'.route('addresses.store').'"', false)
            ->assertSee('<select id="province" name="province" wire:model="province"', false)
            ->assertSee('<option value="تهران"', false)
            ->assertSee('<option value="کهگیلویه و بویراحمد"', false)
            ->assertSee('فهرست شامل ۳۱ استان ایران است.')
            ->assertSee('<input id="city" name="city" wire:model="city" type="text"', false)
            ->assertSee('store-address-section-heading', false)
            ->assertSee('store-address-actions', false)
            ->getContent();

        $this->assertSame(31, substr_count($html, '<option value="') - 1);
        $this->assertStringNotContainsString('<input id="province"', $html);
    }

    public function test_livewire_accepts_a_valid_province_and_preserves_city_and_persian_postal_input(): void
    {
        Livewire::test(AddressEditor::class, ['returnTo' => 'addresses'])
            ->set('recipient_name', 'گیرنده دنا')
            ->set('recipient_mobile', '۰۹۱۲۰۰۰۰۰۸۱')
            ->set('province', 'كهگيلويه و بويراحمد')
            ->set('city', 'سی‌سخت')
            ->set('address_line', 'خیابان اصلی، کوچه سرو، پلاک هجده')
            ->set('postal_code', '۱۲۳۴۵۶۷۸۹۰')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('addresses.index'));

        $this->assertDatabaseHas('customer_addresses', [
            'customer_id' => $this->customer->id,
            'province' => 'کهگیلویه و بویراحمد',
            'city' => 'سی‌سخت',
            'recipient_mobile' => '09120000081',
            'postal_code' => '1234567890',
        ]);
    }

    public function test_manual_fallback_post_accepts_only_listed_provinces_and_preserves_checkout_return(): void
    {
        $attributes = $this->validAddress();

        $this->post(route('addresses.store'), array_merge($attributes, [
            'province' => 'استان نامعتبر',
        ]))->assertSessionHasErrors(['province']);

        $this->assertDatabaseCount('customer_addresses', 0);

        $response = $this->post(route('addresses.store'), array_merge($attributes, [
            'province' => 'تهران',
            'return' => 'checkout',
        ]));

        $address = $this->customer->addresses()->sole();
        $response->assertRedirect(route('checkout.index', ['address_id' => $address->id]));
        $this->assertSame('تهران', $address->province);
        $this->assertSame('سی‌سخت', $address->city);
    }

    public function test_invalid_province_cannot_bypass_the_server_action_using_livewire_or_direct_calls(): void
    {
        $attributes = $this->validAddress();

        Livewire::test(AddressEditor::class)
            ->set('recipient_name', $attributes['recipient_name'])
            ->set('recipient_mobile', $attributes['recipient_mobile'])
            ->set('province', 'استان ساختگی')
            ->set('city', $attributes['city'])
            ->set('address_line', $attributes['address_line'])
            ->set('postal_code', $attributes['postal_code'])
            ->call('save')
            ->assertHasErrors(['province' => 'in']);

        try {
            app(SaveCustomerAddress::class)->execute($this->customer, array_merge($attributes, [
                'province' => 'استان ساختگی',
            ]));
            $this->fail('Invalid provinces must be rejected in the domain action.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('province', $exception->errors());
        }

        $this->assertDatabaseCount('customer_addresses', 0);
    }

    public function test_editing_an_old_address_preselects_canonical_province_without_changing_saved_city(): void
    {
        $address = $this->customer->addresses()->create(array_merge($this->validAddress(), [
            'province' => 'كهگيلويه و بويراحمد',
            'city' => 'سی‌سخت',
        ]));

        Livewire::test(AddressEditor::class, ['addressId' => $address->id, 'returnTo' => 'checkout'])
            ->assertSet('province', 'کهگیلویه و بویراحمد')
            ->assertSet('city', 'سی‌سخت')
            ->assertSee('ذخیره و ادامه ثبت سفارش');

        $this->get(route('addresses.edit', ['address' => $address->id]))
            ->assertOk()
            ->assertSee('value="کهگیلویه و بویراحمد" selected', false);

        // Reading an old address must never silently rewrite stored data.
        $this->assertSame('كهگيلويه و بويراحمد', $address->fresh()->province);
    }

    public function test_unknown_legacy_province_remains_visible_but_requires_an_explicit_valid_choice(): void
    {
        $address = $this->customer->addresses()->create(array_merge($this->validAddress(), [
            'province' => 'استان قدیمی ثبت‌شده',
        ]));

        $this->get(route('addresses.edit', ['address' => $address->id]))
            ->assertOk()
            ->assertSee('استان قبلی: استان قدیمی ثبت‌شده (نیاز به اصلاح)')
            ->assertSee('یک استان معتبر انتخاب کنید.');

        Livewire::test(AddressEditor::class, ['addressId' => $address->id])
            ->assertSet('province', 'استان قدیمی ثبت‌شده')
            ->call('save')
            ->assertHasErrors(['province' => 'in']);

        $this->assertSame('استان قدیمی ثبت‌شده', $address->fresh()->province);

        Livewire::test(AddressEditor::class, ['addressId' => $address->id])
            ->set('province', 'فارس')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('فارس', $address->fresh()->province);
    }

    private function validAddress(): array
    {
        return [
            'recipient_name' => 'گیرنده دنا',
            'recipient_mobile' => '09120000081',
            'province' => 'کهگیلویه و بویراحمد',
            'city' => 'سی‌سخت',
            'address_line' => 'خیابان اصلی، کوچه سرو، پلاک هجده',
            'postal_code' => '1234567890',
            'label' => 'خانه',
        ];
    }
}
