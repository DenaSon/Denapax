<div class="space-y-5">
    @php
        $hasUnresolvedGateway = $order->gatewayAttempts->contains(fn ($attempt) => $attempt->blocksNewPayment());
    @endphp
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="admin-eyebrow">جزئیات سفارش</p>
            <h1 class="admin-page-title admin-copy" dir="ltr">{{ $order->public_reference }}</h1>
            <p class="admin-description">{{ $order->created_at->format('Y/m/d H:i') }} · {{ $order->recipient_name }}</p>
        </div>
        <a wire:navigate class="btn btn-outline" href="{{ route('admin.orders') }}">بازگشت به سفارش‌ها</a>
    </div>

    <section class="admin-surface space-y-4" aria-labelledby="order-summary">
        <h2 id="order-summary" class="admin-section-title">وضعیت و مبالغ قطعی</h2>
        <div class="flex flex-wrap gap-2">
            <span class="admin-state admin-state-{{ \App\Support\AdminLabels::tone($order->order_status) }}">{{ \App\Support\AdminLabels::order($order->order_status) }}</span>
            <span class="admin-state admin-state-{{ \App\Support\AdminLabels::tone($order->payment_status) }}">{{ \App\Support\AdminLabels::payment($order->payment_status) }}</span>
        </div>
        <dl class="grid gap-3 border-t border-[#e0e7df] pt-4 text-sm sm:grid-cols-2">
            <div class="flex justify-between gap-2"><dt>بهای کالاها</dt><dd class="admin-money font-bold">{{ \App\Support\Money::formatToman($order->goods_subtotal_irr) }} تومان</dd></div>
            <div class="flex justify-between gap-2"><dt>هزینه ارسال</dt><dd class="admin-money font-bold">{{ \App\Support\Money::formatToman($order->shipping_fee_irr) }} تومان</dd></div>
            <div class="flex justify-between gap-2"><dt>وزن مرسوله</dt><dd class="font-semibold">{{ number_format($order->shipping_weight_grams) }} گرم</dd></div>
        </dl>
        <div class="flex flex-wrap items-baseline justify-between gap-3 rounded-xl bg-[#eaf2e8] px-5 py-4">
            <span class="font-extrabold">مبلغ نهایی سفارش</span>
            <strong class="admin-money text-xl font-extrabold">{{ \App\Support\Money::formatToman($order->total_irr) }} تومان</strong>
        </div>
    </section>

    @if ($hasUnresolvedGateway)
        <div role="alert" class="admin-alert admin-alert-warning">
            وضعیت یکی از تراکنش‌های درگاه هنوز قطعی نیست. تا تطبیق بانکی، پرداخت مجدد یا لغو سفارش را پیشنهاد نکنید.
            @can('payments.view')
                <a wire:navigate href="{{ route('admin.payments.gateway') }}" class="admin-inline-link mr-2">بررسی تراکنش درگاه</a>
            @endcan
        </div>
    @endif

    <section class="admin-surface space-y-3" aria-labelledby="order-items">
        <h2 id="order-items" class="admin-section-title">اقلام سفارش</h2>
        <div class="admin-table-wrap">
            <table class="table w-full">
                <thead><tr><th>کالا و تنوع</th><th>شناسه کالا</th><th>تعداد</th><th>بهای واحد (تومان)</th></tr></thead>
                <tbody>
                    @foreach($order->items as $item)
                        <tr>
                            <td class="min-w-40 font-bold">{{ $item->product_title_snapshot }} / {{ $item->variant_title_snapshot }}</td>
                            <td dir="ltr" class="admin-copy">{{ $item->sku_snapshot }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td class="admin-money">{{ \App\Support\Money::formatToman($item->unit_price_irr) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-surface space-y-3" aria-labelledby="order-delivery">
        <h2 id="order-delivery" class="admin-section-title">تحویل و رهگیری</h2>
        <p class="text-sm leading-8"><strong>گیرنده:</strong> {{ $order->recipient_name }} · <span dir="ltr" class="inline-block">{{ $order->recipient_mobile }}</span></p>
        <p class="text-sm leading-8"><strong>نشانی:</strong> {{ $order->province }}، {{ $order->city }}، {{ $order->address_line }} · کدپستی: <span dir="ltr" class="inline-block">{{ $order->postal_code }}</span></p>
        @if($order->shipment)
            <div class="rounded-xl bg-[#edf4eb] p-4 text-sm leading-8">
                <p><strong>شرکت حمل:</strong> {{ $order->shipment->carrier }}</p>
                <p><strong>کد رهگیری:</strong> <span dir="ltr" class="admin-copy inline-block font-extrabold">{{ $order->shipment->tracking_code }}</span></p>
            </div>
        @endif
    </section>

    @can('payments.view')
        <section class="admin-surface space-y-3" aria-labelledby="order-payments">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 id="order-payments" class="admin-section-title">سوابق پرداخت</h2>
                <div class="flex flex-wrap gap-3 text-sm">
                    <a wire:navigate href="{{ route('admin.payments') }}" class="admin-inline-link">رسیدها</a>
                    <a wire:navigate href="{{ route('admin.payments.gateway') }}" class="admin-inline-link">تراکنش‌های درگاه</a>
                </div>
            </div>
            @forelse($order->receipts as $receipt)
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#e1e8dd] py-3">
                    <p class="text-sm">رسید شماره {{ $receipt->attempt_number }}
                        <span class="admin-state admin-state-{{ \App\Support\AdminLabels::tone($receipt->status) }}">{{ \App\Support\AdminLabels::receipt($receipt->status) }}</span>
                    </p>
                    <a class="admin-inline-link" href="{{ route('admin.payments.receipt.image', $receipt) }}" target="_blank" rel="noopener noreferrer">تصویر خصوصی رسید</a>
                </div>
            @empty
                <p class="text-sm text-[#637465]">رسید کارت‌به‌کارت ثبت نشده است.</p>
            @endforelse
            @if($order->gatewayAttempts->isNotEmpty())
                <p class="text-sm text-[#637465]">تعداد تلاش‌های درگاه: {{ $order->gatewayAttempts->count() }}؛ برای تطبیق به بخش تراکنش‌های زرین‌پال بروید.</p>
            @endif
        </section>
    @endcan

    <section class="admin-surface space-y-4" aria-labelledby="order-actions">
        <h2 id="order-actions" class="admin-section-title">اقدام بعدی</h2>
        @error('order')<div role="alert" class="admin-alert admin-alert-danger">{{ $message }}</div>@enderror
        @error('tracking')<div role="alert" class="admin-alert admin-alert-danger">{{ $message }}</div>@enderror
        @can('orders.update')
            @if($order->order_status === 'confirmed' && $order->payment_status === 'approved')
                <p class="admin-description">پرداخت تأیید شده و سفارش آماده ورود به مرحله بسته‌بندی است.</p>
                <button type="button" class="btn btn-primary w-full sm:w-auto"
                        wire:confirm="سفارش وارد آماده‌سازی شود؟" wire:click="change('prepare')"
                        wire:loading.attr="disabled" wire:target="change('prepare')">شروع آماده‌سازی</button>
            @endif
            @if($order->order_status === 'awaiting_payment' && in_array($order->payment_status, ['awaiting_receipt', 'rejected'], true) && ! $hasUnresolvedGateway)
                <button type="button" class="btn btn-error btn-outline w-full sm:w-auto"
                        wire:confirm="آیا اطمینان دارید که وجهی دریافت نشده و سفارش لغو شود؟"
                        wire:click="change('cancel')" wire:loading.attr="disabled" wire:target="change('cancel')">لغو سفارش بدون پرداخت</button>
            @endif
        @endcan
        @can('shipping.manage')
            @if($order->order_status === 'preparing')
                <p class="admin-description">فقط پس از تحویل واقعی بسته به شرکت حمل، کد رهگیری را ثبت کنید.</p>
                <div class="grid gap-3 md:grid-cols-2">
                    <x-input label="شرکت حمل" wire:model="carrier" />
                    <x-input label="کد رهگیری مرسوله" wire:model="trackingCode" />
                </div>
                <button type="button" class="btn btn-primary w-full sm:w-auto"
                        wire:confirm="مرسوله واقعاً تحویل شرکت حمل شده است؟"
                        wire:click="change('ship')" wire:loading.attr="disabled" wire:target="change('ship')">ثبت ارسال و کد رهگیری</button>
            @endif
            @if($order->order_status === 'shipped')
                <button type="button" class="btn btn-success w-full sm:w-auto"
                        wire:confirm="تحویل قطعی مرسوله تأیید شود؟" wire:click="change('deliver')"
                        wire:loading.attr="disabled" wire:target="change('deliver')">تأیید تحویل سفارش</button>
            @endif
        @endcan
        @if(! in_array($order->order_status, ['confirmed', 'preparing', 'shipped'], true))
            <p class="admin-description">هر اقدام فقط مطابق وضعیت و مجوز کارکنان در دسترس است.</p>
        @endif
    </section>
</div>
