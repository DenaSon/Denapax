@component('layouts.store', ['title' => 'تسویه‌حساب'])
    <div class="mb-8">
        <p class="text-xs font-bold tracking-widest text-[#b18056]">CHECKOUT / 01</p>
        <h1 class="mt-2 text-3xl font-extrabold">انتخاب نشانی تحویل</h1>
        <p class="mt-2 text-sm leading-7 text-[#637765]">هزینه ارسال براساس وزن نهایی سفارش محاسبه می‌شود و پیش از ثبت نهایی نمایش داده خواهد شد.</p>
    </div>

    <section class="mb-6 rounded-3xl bg-white p-6 md:p-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-extrabold">اقلام سفارش</h2>
            <a href="{{ route('cart.index') }}" class="text-sm underline underline-offset-4">ویرایش سبد خرید</a>
        </div>
        <div class="mt-5 divide-y divide-[#e0e8de]">
            @foreach ($lines as $line)
                <div class="flex justify-between gap-3 py-4 text-sm">
                    <div>
                        <p class="font-bold">{{ $line['variant']->product->title }} / {{ $line['variant']->title }}</p>
                        <p class="mt-1 text-[#647565]">تعداد: {{ $line['quantity'] }}</p>
                        @unless ($line['available'])
                            <p class="mt-1 text-red-700">این تنوع در حال حاضر قابل خرید نیست. سبد را بازبینی کنید.</p>
                        @endunless
                    </div>
                    <span class="shrink-0 font-bold">{{ \App\Support\Money::formatToman($line['total_irr']) }} تومان</span>
                </div>
            @endforeach
        </div>
        <div class="mt-4 flex justify-between border-t border-[#d3dbd0] pt-4 text-sm font-bold">
            <span>جمع کالاها (بدون ارسال)</span>
            <span>{{ \App\Support\Money::formatToman((int) $subtotal) }} تومان</span>
        </div>
    </section>

    <section class="rounded-3xl bg-white p-6 md:p-8">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-extrabold">نشانی‌های ذخیره‌شده</h2>
            <a href="{{ route('addresses.create', ['return' => 'checkout']) }}" class="rounded-full border border-[#54735c] px-4 py-2 text-sm font-bold">+ نشانی جدید</a>
        </div>
        @if ($addresses->isEmpty())
            <p class="rounded-xl bg-[#f4f1e6] p-5 text-sm leading-7">برای محاسبه هزینه ارسال، ابتدا نشانی گیرنده را ثبت کنید.</p>
        @else
            <form method="POST" action="{{ route('checkout.preview') }}" class="space-y-4">
                @csrf
                @foreach ($addresses as $address)
                    <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-[#d8ddd3] p-5">
                        <input type="radio" name="address_id" value="{{ $address->id }}" class="mt-1 accent-[#19392f]"
                               {{ old('address_id', $addresses->first()->id) == $address->id ? 'checked' : '' }} required>
                        <span class="min-w-0 flex-1">
                            <span class="block font-bold">{{ $address->label ?: $address->city }} @if ($address->is_default) <span class="text-xs text-[#537759]">(پیش‌فرض)</span> @endif</span>
                            <span class="mt-2 block text-sm leading-7 text-[#687969]">{{ $address->recipient_name }} · {{ $address->province }}، {{ $address->city }}، {{ $address->address_line }}</span>
                            <span class="mt-1 block text-xs text-[#687969]">موبایل: {{ $address->recipient_mobile }} · کد پستی: {{ $address->postal_code }}</span>
                        </span>
                        <a href="{{ route('addresses.edit', ['address' => $address->id, 'return' => 'checkout']) }}" class="text-xs font-bold underline underline-offset-4">ویرایش</a>
                    </label>
                @endforeach
                @if ($lines->contains(fn ($line) => ! $line['available']))
                    <p class="text-sm text-red-700">برای ادامه ابتدا اقلام ناموجود را از سبد حذف کنید.</p>
                @else
                    <button type="submit" class="mt-4 w-full rounded-full bg-[#19392f] px-6 py-4 font-bold text-white sm:w-auto">محاسبه ارسال و بررسی نهایی</button>
                @endif
            </form>
        @endif
    </section>
@endcomponent
