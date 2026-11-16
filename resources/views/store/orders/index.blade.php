@component('layouts.store', ['title' => 'سفارش‌های من'])
    <div class="mb-9">
        <p class="store-eyebrow">ACCOUNT / ORDERS</p>
        <h1 class="store-heading mt-2">سفارش‌های من</h1>
        <p class="mt-2 text-sm text-[#637765]">پرداخت، وضعیت آماده‌سازی و رهگیری ارسال را اینجا دنبال کنید.</p>
    </div>

    <div class="space-y-4">
        @forelse ($orders as $order)
            @php($hasUnresolvedPayment = $order->gatewayAttempts->contains(fn ($attempt) => $attempt->blocksNewPayment()))
            <a wire:navigate href="{{ route('orders.confirmation', $order->order_number) }}"
               class="store-card store-order-card block p-5 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs text-[#6a7c6b]">شماره سفارش</p>
                        <h2 class="mt-2 text-lg font-extrabold">{{ $order->public_reference }}</h2>
                        <p class="mt-1 text-xs text-[#6a7c6b]">{{ $order->created_at->format('Y/m/d H:i') }}</p>
                    </div>
                    <div class="text-left">
                        <p class="store-amount font-extrabold">{{ \App\Support\Money::formatToman($order->total_irr) }} تومان</p>
                        <p class="mt-2 inline-block rounded-full bg-[#e6efe4] px-3 py-1.5 text-xs font-bold text-[#315940]">
                            {{ match (true) {
                                $order->order_status === 'canceled' => 'لغو شده',
                                $order->order_status === 'delivered' => 'تحویل شده',
                                $order->order_status === 'shipped' => 'ارسال شده',
                                $order->order_status === 'preparing' => 'در حال آماده‌سازی',
                                $order->payment_status === 'approved' => 'پرداخت تأیید شده',
                                $order->payment_status === 'under_review' => 'رسید در حال بررسی',
                                $hasUnresolvedPayment => 'نتیجه پرداخت در حال بررسی',
                                $order->payment_status === 'rejected' => 'رسید نیازمند اصلاح',
                                default => 'در انتظار پرداخت'
                            } }}
                        </p>
                    </div>
                </div>
                <p class="mt-4 text-xs font-semibold text-[#52705b]">مشاهده جزئیات و وضعیت سفارش</p>
            </a>
        @empty
            <div class="store-card p-10 text-center">
                <h2 class="text-lg font-bold">هنوز سفارشی ثبت نکرده‌اید.</h2>
                <a wire:navigate href="{{ route('home') }}#products" class="store-button store-button-primary mt-5">دیدن محصولات</a>
            </div>
        @endforelse
    </div>
    <div class="mt-8">{{ $orders->links() }}</div>
@endcomponent
