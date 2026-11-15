<div class="space-y-5">
    <div>
        <p class="admin-eyebrow">عملیات فروش</p>
        <h1 class="admin-page-title">مدیریت سفارش‌ها</h1>
        <p class="admin-description">سفارش را پیدا کنید، وضعیت پرداخت را ببینید و از صفحه جزئیات اقدام کنید.</p>
    </div>
    <section class="admin-surface space-y-4" aria-label="فیلتر سفارش‌ها">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <x-input label="شماره سفارش" placeholder="مثلاً DN-..." wire:model.live.debounce.350ms="search" />
            <label class="form-control"><span class="label">وضعیت سفارش</span>
                <select wire:model.live="orderStatus" class="select select-bordered w-full">
                    <option value="">همه وضعیت‌ها</option>
                    @foreach(['awaiting_payment', 'confirmed', 'preparing', 'shipped', 'delivered', 'canceled'] as $value)
                        <option value="{{ $value }}">{{ \App\Support\AdminLabels::order($value) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="form-control"><span class="label">وضعیت پرداخت</span>
                <select wire:model.live="paymentStatus" class="select select-bordered w-full">
                    <option value="">همه پرداخت‌ها</option>
                    @foreach(['awaiting_receipt', 'under_review', 'rejected', 'approved'] as $value)
                        <option value="{{ $value }}">{{ \App\Support\AdminLabels::payment($value) }}</option>
                    @endforeach
                </select>
            </label>
            <x-input label="از تاریخ" type="date" wire:model.live="dateFrom" />
            <x-input label="تا تاریخ" type="date" wire:model.live="dateTo" />
        </div>
        <div wire:loading.delay class="text-xs text-[#57705b]" role="status">در حال به‌روزرسانی نتایج...</div>
    </section>
    <section class="admin-surface space-y-4" aria-label="نتایج سفارش‌ها">
        <p class="text-sm text-[#53685a]">سفارش‌های پیدا‌شده: <strong>{{ number_format($orders->total()) }}</strong></p>
        <div class="space-y-3 md:hidden">
            @forelse ($orders as $order)
                <article wire:key="mobile-order-{{ $order->id }}" class="rounded-xl border border-[#dce5da] bg-[#fafcf8] p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <strong dir="ltr">{{ $order->public_reference }}</strong>
                        <strong class="admin-money">{{ \App\Support\Money::formatToman($order->total_irr) }} تومان</strong>
                    </div>
                    <p class="mt-2 text-xs text-[#60745f]">{{ $order->created_at->format('Y/m/d H:i') }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="admin-state admin-state-{{ \App\Support\AdminLabels::tone($order->order_status) }}">{{ \App\Support\AdminLabels::order($order->order_status) }}</span>
                        <span class="admin-state admin-state-{{ \App\Support\AdminLabels::tone($order->payment_status) }}">{{ \App\Support\AdminLabels::payment($order->payment_status) }}</span>
                    </div>
                    <a class="btn btn-primary mt-4 w-full" href="{{ route('admin.orders.show', $order) }}">جزئیات و اقدامات</a>
                </article>
            @empty
                <p class="py-7 text-center text-sm text-[#647568]">سفارشی با این فیلترها پیدا نشد.</p>
            @endforelse
        </div>
        <div class="admin-table-wrap hidden md:block">
            <table class="table w-full">
                <thead><tr><th>شماره سفارش</th><th>تاریخ</th><th>مبلغ (تومان)</th><th>وضعیت سفارش</th><th>وضعیت پرداخت</th><th>عملیات</th></tr></thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr wire:key="order-{{ $order->id }}">
                            <td dir="ltr" class="font-bold">{{ $order->public_reference }}</td>
                            <td>{{ $order->created_at->format('Y/m/d H:i') }}</td>
                            <td class="admin-money font-bold">{{ \App\Support\Money::formatToman($order->total_irr) }}</td>
                            <td><span class="admin-state admin-state-{{ \App\Support\AdminLabels::tone($order->order_status) }}">{{ \App\Support\AdminLabels::order($order->order_status) }}</span></td>
                            <td><span class="admin-state admin-state-{{ \App\Support\AdminLabels::tone($order->payment_status) }}">{{ \App\Support\AdminLabels::payment($order->payment_status) }}</span></td>
                            <td><a class="btn btn-outline btn-sm" href="{{ route('admin.orders.show', $order) }}">جزئیات</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-center">سفارشی با این فیلترها پیدا نشد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $orders->links() }}
    </section>
</div>
