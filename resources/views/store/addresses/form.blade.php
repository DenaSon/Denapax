@component('layouts.store', ['title' => $address->exists ? 'ویرایش نشانی' : 'نشانی جدید'])
    <div class="mb-6">
        <p class="store-eyebrow">نشانی تحویل</p>
        <h1 class="store-heading mt-2">{{ $address->exists ? 'ویرایش نشانی' : 'نشانی جدید' }}</h1>
        <p class="mt-2 text-sm leading-7 text-[#647565]">فقط مشخصات لازم برای ارسال سفارش را وارد کنید؛ برای خرید بعدی ذخیره می‌شود.</p>
    </div>
    <form method="POST" action="{{ $address->exists ? route('addresses.update', $address->id) : route('addresses.store') }}"
          class="store-card space-y-5 p-5 sm:p-8">
        @csrf
        @if ($address->exists) @method('PUT') @endif
        <input type="hidden" name="return" value="{{ $returnTo }}">
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="recipient_name" class="mb-2 block text-sm font-bold">نام و نام خانوادگی گیرنده</label>
                <input id="recipient_name" name="recipient_name" type="text" maxlength="220" required autocomplete="name"
                       value="{{ old('recipient_name', $address->recipient_name) }}"
                       class="store-field min-h-12" aria-required="true">
            </div>
            <div>
                <label for="recipient_mobile" class="mb-2 block text-sm font-bold">موبایل گیرنده</label>
                <input id="recipient_mobile" name="recipient_mobile" type="tel" dir="ltr" inputmode="tel" required autocomplete="tel"
                       value="{{ old('recipient_mobile', $address->recipient_mobile ?: $customerMobile) }}" placeholder="09123456789"
                       class="store-field min-h-12 text-left">
                <p class="mt-1 text-xs text-[#768675]">شماره حساب شما به‌صورت پیش‌فرض وارد شده و قابل تغییر است.</p>
            </div>
            <div>
                <label for="province" class="mb-2 block text-sm font-bold">استان</label>
                <input id="province" name="province" type="text" maxlength="100" required autocomplete="address-level1"
                       value="{{ old('province', $address->province) }}" class="store-field min-h-12">
            </div>
            <div>
                <label for="city" class="mb-2 block text-sm font-bold">شهر</label>
                <input id="city" name="city" type="text" maxlength="100" required autocomplete="address-level2"
                       value="{{ old('city', $address->city) }}" class="store-field min-h-12">
            </div>
        </div>
        <div>
            <label for="address_line" class="mb-2 block text-sm font-bold">نشانی دقیق</label>
            <textarea id="address_line" name="address_line" rows="3" minlength="8" maxlength="2000" required
                      autocomplete="street-address" placeholder="خیابان، کوچه، پلاک و واحد"
                      class="store-field">{{ old('address_line', $address->address_line) }}</textarea>
        </div>
        <div class="grid items-end gap-5 sm:grid-cols-2">
            <div>
                <label for="postal_code" class="mb-2 block text-sm font-bold">کد پستی ده‌رقمی</label>
                <input id="postal_code" name="postal_code" type="text" dir="ltr" inputmode="numeric" maxlength="10" required
                       autocomplete="postal-code" placeholder="1234567890"
                       value="{{ old('postal_code', $address->postal_code) }}" class="store-field min-h-12 text-left">
            </div>
            <div>
                <label for="label" class="mb-2 block text-sm font-bold">نام نشانی (اختیاری)</label>
                <input id="label" name="label" type="text" maxlength="80" value="{{ old('label', $address->label) }}"
                       placeholder="مثلاً خانه یا محل کار" class="store-field min-h-12">
            </div>
        </div>
        <div class="flex flex-col gap-3 border-t border-[#e0e7dc] pt-5 sm:flex-row sm:items-center">
            <button type="submit" class="store-button store-button-primary min-h-14 flex-1">
                {{ $returnTo === 'checkout' ? 'ذخیره نشانی و ادامه خرید' : 'ذخیره نشانی' }}
            </button>
            <a href="{{ $returnTo === 'checkout' ? route('checkout.index') : route('addresses.index') }}"
               class="store-inline-link justify-center px-4">انصراف</a>
        </div>
    </form>
@endcomponent
