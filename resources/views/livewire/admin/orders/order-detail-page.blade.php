<div class="space-y-5">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">سفارش {{ $order->public_reference }}</h1>
        <a class="btn btn-ghost" href="{{ route('admin.orders') }}">بازگشت</a>
    </div>
    <x-card title="وضعیت و مبالغ قطعی سفارش">
        <p>وضعیت سفارش: <strong>{{ $order->order_status }}</strong> · وضعیت پرداخت: <strong>{{ $order->payment_status }}</strong></p>
        <p>مجموع کالا: {{ \App\Support\Money::formatToman($order->goods_subtotal_irr) }} تومان</p>
        <p>هزینه پست: {{ \App\Support\Money::formatToman($order->shipping_fee_irr) }} تومان</p>
        <p>جمع کل: <strong>{{ \App\Support\Money::formatToman($order->total_irr) }} تومان</strong></p>
        <p>وزن مرسوله: {{ $order->shipping_weight_grams }} گرم</p>
    </x-card>
    <x-card title="اقلام ثبت‌شده (Snapshot)">
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>کالا و تنوع</th><th>SKU</th><th>تعداد</th><th>بهای واحد (تومان)</th></tr></thead>
                <tbody>
                @foreach($order->items as $item)
                    <tr><td>{{ $item->product_title_snapshot }} / {{ $item->variant_title_snapshot }}</td><td>{{ $item->sku_snapshot }}</td><td>{{ $item->quantity }}</td><td>{{ \App\Support\Money::formatToman($item->unit_price_irr) }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
    <x-card title="تحویل و مرسوله">
        <p>گیرنده: {{ $order->recipient_name }} · {{ $order->recipient_mobile }}</p>
        <p>نشانی: {{ $order->province }}، {{ $order->city }}، {{ $order->address_line }} · کدپستی {{ $order->postal_code }}</p>
        @if($order->shipment)
            <p>شرکت حمل: {{ $order->shipment->carrier }} · کد رهگیری: <strong>{{ $order->shipment->tracking_code }}</strong></p>
        @endif
    </x-card>
    @can('payments.view')
        <x-card title="تاریخچه رسیدهای پرداخت">
            @forelse($order->receipts as $receipt)
                <p>تلاش {{ $receipt->attempt_number }} · {{ $receipt->status }} ·
                    <a class="link" href="{{ route('admin.payments.receipt.image', $receipt) }}" target="_blank" rel="noopener noreferrer">تصویر خصوصی</a></p>
            @empty
                <p>رسیدی ثبت نشده است.</p>
            @endforelse
        </x-card>
    @endcan
    <x-card title="عملیات سفارش">
        @error('order')<p class="text-error">{{ $message }}</p>@enderror
        @can('orders.update')
            @if($order->order_status === 'confirmed' && $order->payment_status === 'approved')
                <x-button label="شروع آماده‌سازی" class="btn-primary" wire:confirm="سفارش وارد آماده‌سازی شود؟" wire:click="change('prepare')" />
            @endif
            @if($order->order_status === 'awaiting_payment' && in_array($order->payment_status, ['awaiting_receipt', 'rejected'], true))
                <x-button label="لغو سفارش بدون پرداخت" class="btn-error btn-outline" wire:confirm="آیا سفارش لغو شود؟" wire:click="change('cancel')" />
            @endif
        @endcan
        @can('shipping.manage')
            @if($order->order_status === 'preparing')
                <div class="grid gap-3 md:grid-cols-2">
                    <x-input label="شرکت حمل" wire:model="carrier" />
                    <x-input label="کد رهگیری پستی" wire:model="trackingCode" />
                </div>
                <x-button label="ثبت ارسال و کد رهگیری" class="btn-primary mt-3" wire:confirm="مرسوله واقعاً تحویل شرکت حمل شده است؟" wire:click="change('ship')" />
            @endif
            @if($order->order_status === 'shipped')
                <x-button label="تأیید تحویل مرسوله" class="btn-success" wire:confirm="تحویل قطعی مرسوله تأیید شود؟" wire:click="change('deliver')" />
            @endif
        @endcan
    </x-card>
</div>
