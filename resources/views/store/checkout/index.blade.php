@component('layouts.store', ['title' => 'تکمیل سفارش'])
    <div class="mb-6">
        <p class="text-xs font-semibold tracking-widest text-[#a3744e]">سبد خرید ← تحویل ← پرداخت</p>
        <h1 class="mt-2 text-2xl font-extrabold sm:text-3xl">تکمیل سفارش</h1>
        <p class="mt-2 text-sm leading-7 text-[#637765]">نشانی تحویل و مبلغ نهایی را بررسی کنید؛ با یک تأیید به صفحه پرداخت می‌روید.</p>
    </div>

    <section class="mb-5 rounded-2xl bg-white p-5 shadow-sm sm:p-7" aria-labelledby="delivery-title">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 id="delivery-title" class="text-lg font-extrabold">۱. نشانی تحویل</h2>
            <a href="{{ route('addresses.create', ['return' => 'checkout']) }}"
               class="inline-flex min-h-11 items-center rounded-full border border-[#53735c] px-4 text-sm font-bold">+ نشانی جدید</a>
        </div>

        @if ($addresses->isEmpty())
            <p class="mt-4 rounded-xl bg-[#f4f1e6] p-4 text-sm leading-7">
                برای محاسبه ارسال، ابتدا نشانی و گیرنده را وارد کنید. این اطلاعات برای خرید بعدی ذخیره می‌شوند.
            </p>
            <a href="{{ route('addresses.create', ['return' => 'checkout']) }}"
               class="mt-4 inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-[#19392f] px-6 font-bold text-white sm:w-auto">
                ثبت نشانی تحویل
            </a>
        @else
            <div class="mt-4 space-y-3">
                @foreach ($addresses as $address)
                    <div class="rounded-2xl border p-4 {{ $selectedAddress?->id === $address->id ? 'border-[#346349] bg-[#f0f5ed]' : 'border-[#d8ddd3] bg-white' }}">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="font-bold">
                                {{ $address->label ?: $address->city }}
                                @if ($selectedAddress?->id === $address->id)
                                    <span class="mr-2 text-xs font-semibold text-[#38684b]">✓ انتخاب‌شده</span>
                                @endif
                            </p>
                            <div class="flex items-center gap-3">
                                <a href="{{ route('addresses.edit', ['address' => $address->id, 'return' => 'checkout']) }}"
                                   class="inline-flex min-h-11 items-center text-sm font-semibold underline underline-offset-4">ویرایش</a>
                                @if ($selectedAddress?->id !== $address->id)
                                    <form method="GET" action="{{ route('checkout.index') }}">
                                        <button type="submit" name="address_id" value="{{ $address->id }}"
                                                class="min-h-11 rounded-full bg-[#19392f] px-4 text-sm font-bold text-white">
                                            انتخاب این نشانی
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                        <p class="mt-2 text-sm leading-7 text-[#657967]">{{ $address->recipient_name }} · {{ $address->province }}، {{ $address->city }}، {{ $address->address_line }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <section class="rounded-2xl bg-white p-5 shadow-sm sm:p-7" aria-labelledby="order-summary-title">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 id="order-summary-title" class="text-lg font-extrabold">۲. خلاصه سفارش</h2>
            <a href="{{ route('cart.index') }}" class="inline-flex min-h-11 items-center text-sm font-semibold underline underline-offset-4">ویرایش سبد</a>
        </div>
        <div class="mt-3 divide-y divide-[#e0e8de]">
            @foreach ($lines as $line)
                <div class="flex items-start justify-between gap-3 py-3 text-sm">
                    <div>
                        <p class="font-semibold">{{ $line['variant']->product->title }} / {{ $line['variant']->title }}</p>
                        <p class="mt-1 text-[#687969]">تعداد: {{ $line['quantity'] }}</p>
                        @unless ($line['available'])
                            <p class="mt-1 text-red-700">این تنوع اکنون قابل خرید نیست.</p>
                        @endunless
                    </div>
                    <span class="shrink-0 font-bold">{{ \App\Support\Money::formatToman($line['total_irr']) }} تومان</span>
                </div>
            @endforeach
        </div>

        <dl class="mt-4 space-y-3 border-t border-[#d3dbd0] pt-5 text-sm">
            <div class="flex items-center justify-between gap-3">
                <dt>جمع کالاها</dt>
                <dd class="font-semibold">{{ \App\Support\Money::formatToman((int) $subtotal) }} تومان</dd>
            </div>
            <div class="flex items-center justify-between gap-3">
                <dt>هزینه ارسال</dt>
                <dd class="font-semibold">
                    {{ $quote !== null ? \App\Support\Money::formatToman((int) $quote['shipping_fee_irr']).' تومان' : 'پس از ثبت نشانی' }}
                </dd>
            </div>
            @if ($quote !== null)
                <div class="flex items-center justify-between gap-3 border-t border-[#d3dbd0] pt-4 text-lg font-extrabold">
                    <dt>مبلغ نهایی</dt>
                    <dd>{{ \App\Support\Money::formatToman((int) $quote['total_irr']) }} تومان</dd>
                </div>
            @endif
        </dl>

        @if ($quoteError)
            <div role="alert" class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm leading-7 text-red-800">
                {{ $quoteError }}
                <a href="{{ route('cart.index') }}" class="mr-2 font-bold underline underline-offset-4">اصلاح سبد خرید</a>
            </div>
        @elseif ($quote !== null && $checkoutKey !== null)
            <p class="mt-5 text-sm leading-7 text-[#657967]">
                این مبلغ شامل کالا و ارسال است. پرداخت در مرحله بعد انجام می‌شود؛ ثبت سفارش به‌تنهایی به معنای پرداخت نیست.
            </p>
            <form method="POST" action="{{ route('checkout.confirm') }}" class="mt-5">
                @csrf
                <input type="hidden" name="checkout_key" value="{{ $checkoutKey }}">
                <button type="submit" class="min-h-14 w-full rounded-2xl bg-[#19392f] px-6 py-4 text-base font-extrabold text-white transition hover:bg-[#2b5741] focus-visible:outline focus-visible:outline-4 focus-visible:outline-offset-2 focus-visible:outline-[#ae855a]">
                    ثبت سفارش و ادامه به پرداخت
                </button>
            </form>
            <p class="mt-3 text-center text-xs leading-6 text-[#728272]">در صفحه بعد می‌توانید زرین‌پال یا کارت‌به‌کارت را انتخاب کنید.</p>
        @endif
    </section>
@endcomponent
