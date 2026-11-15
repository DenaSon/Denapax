@component('layouts.store', ['title' => 'سفارش‌های من'])
    <div class="mb-9">
        <p class="text-xs font-bold tracking-widest text-[#b18056]">ACCOUNT / ORDERS</p>
        <h1 class="mt-2 text-3xl font-extrabold">سفارش‌های من</h1>
        <p class="mt-2 text-sm text-[#637765]">پرداخت، وضعیت آماده‌سازی و رهگیری ارسال را اینجا دنبال کنید.</p>
    </div>

    <div class="space-y-4">
        @forelse ($orders as $order)
            <a href="{{ route('orders.confirmation', $order->order_number) }}"
               class="block rounded-2xl border border-[#d7dfd4] bg-white p-6 transition hover:border-[#588166] hover:shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs text-[#6a7c6b]">شماره سفارش</p>
                        <h2 class="mt-2 text-lg font-extrabold">{{ $order->public_reference }}</h2>
                        <p class="mt-1 text-xs text-[#6a7c6b]">{{ $order->created_at->format('Y/m/d H:i') }}</p>
                    </div>
                    <div class="text-left">
                        <p class="font-extrabold">{{ \App\Support\Money::formatToman($order->total_irr) }} تومان</p>
                        <p class="mt-2 text-xs font-semibold text-[#365b42]">
                            {{ match (true) {
                                $order->order_status === 'canceled' => 'لغو شده',
                                $order->order_status === 'delivered' => 'تحویل شده',
                                $order->order_status === 'shipped' => 'ارسال شده',
                                $order->order_status === 'preparing' => 'در حال آماده‌سازی',
                                $order->payment_status === 'approved' => 'پرداخت تأیید شده',
                                $order->payment_status === 'under_review' => 'رسید در حال بررسی',
                                $order->payment_status === 'rejected' => 'رسید نیازمند اصلاح',
                                default => 'در انتظار پرداخت'
                            } }}
                        </p>
                    </div>
                </div>
                <p class="mt-4 text-xs font-semibold text-[#52705b]">مشاهده جزئیات و پرداخت ←</p>
            </a>
        @empty
            <div class="rounded-2xl bg-white p-12 text-center">
                <h2 class="text-lg font-bold">هنوز سفارشی ثبت نکرده‌اید.</h2>
                <a href="{{ route('home') }}#products" class="mt-5 inline-block rounded-full bg-[#19392f] px-6 py-3 text-sm font-bold text-white">دیدن محصولات</a>
            </div>
        @endforelse
    </div>
    <div class="mt-8">{{ $orders->links() }}</div>
@endcomponent
