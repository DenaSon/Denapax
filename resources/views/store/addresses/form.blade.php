@component('layouts.store', ['title' => $address->exists ? 'ویرایش نشانی' : 'نشانی جدید'])
    <div class="mb-8">
        <p class="text-xs font-semibold tracking-widest text-[#b18056]">ACCOUNT / DELIVERY</p>
        <h1 class="mt-2 text-3xl font-extrabold">{{ $address->exists ? 'ویرایش نشانی تحویل' : 'نشانی تحویل جدید' }}</h1>
        <p class="mt-3 text-sm leading-7 text-[#647565]">اطلاعات گیرنده را دقیق وارد کنید؛ این اطلاعات هنگام ثبت سفارش ذخیره می‌شوند.</p>
    </div>
    <form method="POST" action="{{ $address->exists ? route('addresses.update', $address->id) : route('addresses.store') }}" class="space-y-6 rounded-3xl bg-white p-6 shadow-sm md:p-10">
        @csrf
        @if ($address->exists) @method('PUT') @endif
        <input type="hidden" name="return" value="{{ $returnTo }}">
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="label" class="mb-2 block text-sm font-bold">عنوان نشانی (اختیاری)</label>
                <input id="label" name="label" type="text" maxlength="80" value="{{ old('label', $address->label) }}" placeholder="خانه، محل کار..."
                       class="w-full rounded-xl border border-[#ced7ca] p-3">
            </div>
            <div>
                <label for="recipient_name" class="mb-2 block text-sm font-bold">نام و نام خانوادگی گیرنده</label>
                <input id="recipient_name" name="recipient_name" type="text" maxlength="220" required autocomplete="name"
                       value="{{ old('recipient_name', $address->recipient_name) }}" class="w-full rounded-xl border border-[#ced7ca] p-3">
            </div>
            <div>
                <label for="recipient_mobile" class="mb-2 block text-sm font-bold">موبایل گیرنده</label>
                <input id="recipient_mobile" name="recipient_mobile" type="tel" dir="ltr" inputmode="tel" required autocomplete="tel"
                       value="{{ old('recipient_mobile', $address->recipient_mobile) }}" placeholder="09123456789" class="w-full rounded-xl border border-[#ced7ca] p-3 text-left">
            </div>
            <div>
                <label for="postal_code" class="mb-2 block text-sm font-bold">کد پستی ده‌رقمی</label>
                <input id="postal_code" name="postal_code" type="text" dir="ltr" inputmode="numeric" maxlength="16" required autocomplete="postal-code"
                       value="{{ old('postal_code', $address->postal_code) }}" class="w-full rounded-xl border border-[#ced7ca] p-3 text-left">
            </div>
            <div>
                <label for="province" class="mb-2 block text-sm font-bold">استان</label>
                <input id="province" name="province" type="text" maxlength="100" required autocomplete="address-level1"
                       value="{{ old('province', $address->province) }}" class="w-full rounded-xl border border-[#ced7ca] p-3">
            </div>
            <div>
                <label for="city" class="mb-2 block text-sm font-bold">شهر</label>
                <input id="city" name="city" type="text" maxlength="100" required autocomplete="address-level2"
                       value="{{ old('city', $address->city) }}" class="w-full rounded-xl border border-[#ced7ca] p-3">
            </div>
        </div>
        <div>
            <label for="address_line" class="mb-2 block text-sm font-bold">نشانی دقیق (خیابان، کوچه، پلاک، واحد)</label>
            <textarea id="address_line" name="address_line" rows="3" minlength="8" maxlength="2000" required autocomplete="street-address"
                      class="w-full rounded-xl border border-[#ced7ca] p-3">{{ old('address_line', $address->address_line) }}</textarea>
        </div>
        <div class="flex flex-wrap items-center gap-4">
            <button type="submit" class="rounded-full bg-[#19392f] px-8 py-3 text-sm font-bold text-white">ذخیره نشانی</button>
            <a href="{{ $returnTo === 'checkout' ? route('checkout.index') : route('addresses.index') }}" class="text-sm font-semibold underline underline-offset-4">انصراف</a>
        </div>
    </form>
@endcomponent
