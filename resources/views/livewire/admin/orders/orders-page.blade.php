<div class="space-y-5">
    <h1 class="text-2xl font-bold">مدیریت سفارش‌های دنا</h1>
    <x-card>
        <div class="grid gap-3 md:grid-cols-3">
            <x-input label="جستجو با شماره سفارش" wire:model.live.debounce.350ms="search" />
            <label class="form-control"><span class="label">وضعیت سفارش</span>
                <select wire:model.live="orderStatus" class="select w-full">
                    <option value="">همه</option>
                    @foreach(['awaiting_payment' => 'در انتظار پرداخت', 'confirmed' => 'تأییدشده', 'preparing' => 'در حال آماده‌سازی', 'shipped' => 'ارسال‌شده', 'delivered' => 'تحویل‌شده', 'canceled' => 'لغوشده'] as $value => $title)
                        <option value="{{ $value }}">{{ $title }}</option>
                    @endforeach
                </select>
            </label>
            <label class="form-control"><span class="label">وضعیت پرداخت</span>
                <select wire:model.live="paymentStatus" class="select w-full">
                    <option value="">همه</option>
                    @foreach(['awaiting_receipt' => 'در انتظار رسید', 'under_review' => 'در حال بررسی', 'rejected' => 'ردشده', 'approved' => 'تأییدشده'] as $value => $title)
                        <option value="{{ $value }}">{{ $title }}</option>
                    @endforeach
                </select>
            </label>
            <x-input label="از تاریخ (YYYY-MM-DD)" type="date" wire:model.live="dateFrom" />
            <x-input label="تا تاریخ (YYYY-MM-DD)" type="date" wire:model.live="dateTo" />
        </div>
    </x-card>
    <x-card>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>شماره سفارش</th><th>تاریخ</th><th>مبلغ (تومان)</th><th>وضعیت</th><th>پرداخت</th><th>عملیات</th></tr></thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr wire:key="order-{{ $order->id }}">
                            <td dir="ltr">{{ $order->order_number }}</td>
                            <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>
                            <td>{{ number_format(intdiv($order->total_irr, 10)) }}</td>
                            <td>{{ $order->order_status }}</td>
                            <td>{{ $order->payment_status }}</td>
                            <td><x-button label="جزئیات" class="btn-sm" link="{{ route('admin.orders.show', $order) }}" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="6">سفارشی پیدا نشد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $orders->links() }}</div>
    </x-card>
</div>
