@component('layouts.store', ['title' => 'تکمیل سفارش'])
    <div class="mb-7">
        <p class="store-eyebrow">سبد خرید ← تحویل ← پرداخت</p>
        <h1 class="store-heading mt-2">تکمیل سفارش</h1>
        <p class="store-muted mt-2 text-sm">نشانی تحویل و مبلغ نهایی را بررسی کنید؛ با یک تأیید به صفحه پرداخت می‌روید.</p>
    </div>

    <section class="store-card mb-5 p-5 sm:p-7" aria-labelledby="delivery-title">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 id="delivery-title" class="text-lg font-extrabold">۱. نشانی تحویل</h2>
            <a wire:navigate href="{{ route('addresses.create', ['return' => 'checkout']) }}"
               class="store-button store-button-outline min-h-11 !px-4 !py-2 text-sm">+ نشانی جدید</a>
        </div>

        @if ($addresses->isEmpty())
            <p class="mt-4 rounded-xl bg-[#f4f1e6] p-4 text-sm leading-7">
                برای محاسبه ارسال، ابتدا نشانی و گیرنده را وارد کنید. این اطلاعات برای خرید بعدی ذخیره می‌شوند.
            </p>
            <a wire:navigate href="{{ route('addresses.create', ['return' => 'checkout']) }}"
               class="store-button store-button-primary mt-4 w-full sm:w-auto">
                ثبت نشانی تحویل
            </a>
        @else
            <livewire:store.address-picker :selected-address-id="$selectedAddress?->id" />
        @endif
    </section>

    <section class="store-card p-5 sm:p-7" aria-labelledby="order-summary-title">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 id="order-summary-title" class="text-lg font-extrabold">۲. خلاصه سفارش</h2>
            <a wire:navigate href="{{ route('cart.index') }}" class="store-inline-link">ویرایش سبد</a>
        </div>
        <div class="mt-3 divide-y divide-[#e0e8de]">
            @foreach ($lines as $line)
                <div class="flex items-start justify-between gap-3 py-3 text-sm">
                    <div>
                        <p class="store-amount font-semibold">{{ $line['variant']->product->title }} / {{ $line['variant']->title }}</p>
                        <p class="mt-1 text-[#687969]">تعداد: {{ $line['quantity'] }}</p>
                        @unless ($line['available'])
                            <p class="mt-1 text-red-700">این تنوع اکنون قابل خرید نیست.</p>
                        @endunless
                    </div>
                    <span class="store-amount shrink-0 font-bold">{{ \App\Support\Money::formatToman($line['total_irr']) }} تومان</span>
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
                <div class="store-checkout-total mt-4 text-lg font-extrabold">
                    <dt>مبلغ نهایی</dt>
                    <dd>{{ \App\Support\Money::formatToman((int) $quote['total_irr']) }} تومان</dd>
                </div>
            @endif
        </dl>

        @if ($quoteError)
            <div role="alert" class="store-error-summary mt-5 rounded-xl p-4 text-sm leading-7">
                {{ $quoteError }}
                <a wire:navigate href="{{ route('cart.index') }}" class="mr-2 font-bold underline underline-offset-4">اصلاح سبد خرید</a>
            </div>
        @elseif ($quote !== null && $checkoutKey !== null)
            <p class="mt-5 text-sm leading-7 text-[#657967]">
                این مبلغ شامل کالا و ارسال است. پرداخت در مرحله بعد انجام می‌شود؛ ثبت سفارش به‌تنهایی به معنای پرداخت نیست.
            </p>
            <form method="POST" action="{{ route('checkout.confirm') }}" class="mt-5" data-submit-once>
                @csrf
                <input type="hidden" name="checkout_key" value="{{ $checkoutKey }}">
                <button type="submit" class="store-button store-button-primary min-h-14 w-full text-base" data-busy-label="در حال ثبت سفارش...">
                    ثبت سفارش و ادامه به پرداخت
                </button>
            </form>
            <p class="mt-3 text-center text-xs leading-6 text-[#728272]">در صفحه بعد روش‌های پرداخت فعال فروشگاه و دستورالعمل ارسال رسید را مشاهده می‌کنید.</p>
        @endif
    </section>
@endcomponent
