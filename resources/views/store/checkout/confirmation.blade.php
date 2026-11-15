@component('layouts.store', ['title' => 'سفارش ثبت شد'])
    <section class="rounded-3xl bg-white p-6 shadow-sm md:p-10">
        <p class="text-xs font-bold tracking-widest text-[#b18056]">ORDER / CONFIRMED RECORD</p>
        <h1 class="mt-3 text-3xl font-extrabold">سفارش شما ثبت شد</h1>
        <p class="mt-3 text-sm leading-8 text-[#647565]">اطلاعات سفارش ثبت و ذخیره شد. نهایی شدن خرید منوط به تأیید پرداخت است.</p>
        <div class="mt-6 rounded-2xl bg-[#eaf0e5] p-5">
            <p class="text-xs text-[#687969]">شماره پیگیری سفارش</p>
            <p class="mt-2 break-all text-xl font-extrabold" dir="ltr">{{ $order->public_reference }}</p>
            <p class="mt-2 text-xs text-[#687969]">وضعیت سفارش: {{ $order->order_status === 'awaiting_payment' ? 'در انتظار پرداخت' : $order->order_status }}</p>
        </div>
        <h2 class="mt-8 text-lg font-extrabold">جزئیات سفارش</h2>
        <div class="mt-4 divide-y divide-[#e2e8df]">
            @foreach ($order->items as $item)
                <div class="flex justify-between gap-4 py-4 text-sm">
                    <div>
                        <p class="font-bold">{{ $item->product_title_snapshot }} / {{ $item->variant_title_snapshot }}</p>
                        <p class="mt-1 text-[#637765]">تعداد: {{ $item->quantity }}</p>
                    </div>
                    <span class="shrink-0">{{ \App\Support\Money::formatToman((int) $item->line_total_irr) }} تومان</span>
                </div>
            @endforeach
        </div>
        <dl class="mt-6 space-y-3 text-sm">
            <div class="flex justify-between"><dt>جمع کالاها</dt><dd>{{ \App\Support\Money::formatToman($order->goods_subtotal_irr) }} تومان</dd></div>
            <div class="flex justify-between"><dt>ارسال</dt><dd>{{ \App\Support\Money::formatToman($order->shipping_fee_irr) }} تومان</dd></div>
            <div class="flex justify-between border-t border-[#d3dbd0] pt-4 font-extrabold"><dt>جمع نهایی</dt><dd>{{ \App\Support\Money::formatToman($order->total_irr) }} تومان</dd></div>
        </dl>
        <section class="mt-8 rounded-xl bg-[#f4f1e6] p-5 text-sm leading-8">
            <h2 class="font-bold">نشانی تحویل</h2>
            <p>{{ $order->recipient_name }} · {{ $order->recipient_mobile }}</p>
            <p>{{ $order->province }}، {{ $order->city }}، {{ $order->address_line }}</p>
            <p>کد پستی: {{ $order->postal_code }}</p>
        </section>
        <p class="mt-6 text-sm leading-8 text-[#8a643e]">پرداخت تحت وب و ارسال رسید هنوز فعال نشده است. تا فعال شدن این بخش، از واریز وجه خودداری کنید.</p>
        <div class="mt-8 flex flex-wrap gap-4">
            <a href="{{ route('home') }}#products" class="rounded-full bg-[#19392f] px-6 py-3 text-sm font-bold text-white">بازگشت به محصولات</a>
            <a href="{{ route('cart.index') }}" class="rounded-full border border-[#19392f] px-6 py-3 text-sm font-bold">سبد خرید</a>
        </div>
    </section>
@endcomponent
