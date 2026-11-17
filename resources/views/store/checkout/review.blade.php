@component('layouts.store', ['title' => 'بررسی نهایی سفارش'])
    <div class="mb-8">
        <p class="text-xs font-bold tracking-widest text-[#b18056]">CHECKOUT / 02</p>
        <h1 class="mt-2 text-3xl font-extrabold">بررسی و تأیید سفارش</h1>
        <p class="mt-2 text-sm leading-7 text-[#637765]">مبلغ کالا و ارسال، نشانی و وزن نهایی را بررسی کنید. پس از ثبت، اقلام و مبلغ این سفارش ثابت می‌مانند.</p>
    </div>
    <section class="rounded-3xl bg-white p-6 md:p-10">
        <h2 class="text-lg font-bold">اقلام انتخابی</h2>
        <div class="mt-4 divide-y divide-[#e2e8df]">
            @foreach ($quote['items'] as $item)
                <div class="flex justify-between gap-4 py-4 text-sm">
                    <div>
                        <p class="font-bold">{{ $item['product_title_snapshot'] }} / {{ $item['variant_title_snapshot'] }}</p>
                        <p class="mt-1 text-[#687969]">تعداد: {{ $item['quantity'] }}</p>
                    </div>
                    <span class="shrink-0 font-bold">{{ \App\Support\Money::formatToman((int) $item['line_total_irr']) }} تومان</span>
                </div>
            @endforeach
        </div>
        <div class="mt-6 rounded-2xl bg-[#f1f3ea] p-5">
            <h2 class="font-extrabold">گیرنده و نشانی</h2>
            <p class="mt-2 text-sm leading-8">{{ $quote['address']['recipient_name'] }} · {{ $quote['address']['recipient_mobile'] }}</p>
            <p class="text-sm leading-8">{{ $quote['address']['province'] }}، {{ $quote['address']['city'] }}، {{ $quote['address']['address_line'] }}</p>
            <p class="text-sm">کد پستی: {{ $quote['address']['postal_code'] }}</p>
        </div>
        <dl class="mt-6 space-y-3 text-sm">
            <div class="flex justify-between gap-3"><dt>جمع کالاها</dt><dd>{{ \App\Support\Money::formatToman((int) $quote['goods_subtotal_irr']) }} تومان</dd></div>
            <div class="flex justify-between gap-3"><dt>وزن ارسالی با بسته‌بندی</dt><dd>{{ number_format((int) $quote['shipping_weight_grams']) }} گرم</dd></div>
            <div class="flex justify-between gap-3"><dt>هزینه ارسال</dt><dd>{{ \App\Support\Money::formatToman((int) $quote['shipping_fee_irr']) }} تومان</dd></div>
            <div class="flex justify-between gap-3 border-t border-[#d3dbd0] pt-5 text-lg font-extrabold">
                <dt>مبلغ نهایی</dt>
                <dd>{{ \App\Support\Money::formatToman((int) $quote['total_irr']) }} تومان</dd>
            </div>
        </dl>
        <x-store.payment-method-note :gateway-enabled="$gatewayEnabled" :manual-available="$manualAvailable" class="mt-7" />
        <div class="mt-7 flex flex-wrap items-center gap-4">
            <form method="POST" action="{{ route('checkout.confirm') }}">
                @csrf
                <input type="hidden" name="checkout_key" value="{{ $checkoutKey }}">
                <button type="submit" class="rounded-full bg-[#19392f] px-8 py-4 font-bold text-white">ثبت سفارش و ادامه به پرداخت</button>
            </form>
            <a wire:navigate href="{{ route('checkout.index') }}" class="text-sm font-bold underline underline-offset-4">تغییر نشانی یا سبد</a>
        </div>
    </section>
@endcomponent
