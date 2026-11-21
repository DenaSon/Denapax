<div class="space-y-7">
    <div>
        <p class="admin-eyebrow">مرکز عملیات فروشگاه</p>
        <h1 class="admin-page-title">داشبورد دناپکس</h1>
        <p class="admin-description">کارهایی که امروز نیازمند پیگیری‌اند، در یک نگاه.</p>
    </div>

    <section aria-labelledby="orders-overview" class="space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 id="orders-overview" class="admin-section-title">وضعیت سفارش‌ها</h2>
            <a wire:navigate class="admin-inline-link" href="{{ route('admin.orders') }}">مشاهده همه سفارش‌ها</a>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <a wire:navigate href="{{ route('admin.orders', ['orderStatus' => 'confirmed', 'paymentStatus' => 'approved']) }}" class="admin-metric">
                <span class="admin-metric-label">آماده شروع آماده‌سازی</span>
                <span class="admin-metric-value">{{ number_format($readyToPrepareCount) }}</span>
                <span class="admin-metric-hint">سفارش‌های پرداخت‌شده و تأییدشده</span>
            </a>
            <a wire:navigate href="{{ route('admin.orders', ['orderStatus' => 'preparing']) }}" class="admin-metric">
                <span class="admin-metric-label">در حال آماده‌سازی</span>
                <span class="admin-metric-value">{{ number_format($preparingCount) }}</span>
                <span class="admin-metric-hint">آماده ثبت کد رهگیری</span>
            </a>
            <a wire:navigate href="{{ route('admin.orders', ['orderStatus' => 'shipped']) }}" class="admin-metric">
                <span class="admin-metric-label">ارسال‌شده</span>
                <span class="admin-metric-value">{{ number_format($shippedCount) }}</span>
                <span class="admin-metric-hint">در انتظار تأیید تحویل</span>
            </a>
            <a wire:navigate href="{{ route('admin.orders', ['orderStatus' => 'awaiting_payment']) }}" class="admin-metric">
                <span class="admin-metric-label">در انتظار پرداخت</span>
                <span class="admin-metric-value">{{ number_format($awaitingPaymentCount) }}</span>
                <span class="admin-metric-hint">هنوز نباید برای ارسال آماده شوند</span>
            </a>
        </div>
    </section>

    <section class="admin-surface space-y-4" aria-labelledby="visits-overview">
        <div class="space-y-1">
            <h2 id="visits-overview" class="admin-section-title">آمار بازدید فروشگاه</h2>
            <p class="text-xs text-[#617463]">صفحه اصلی و صفحات محصولات · از زمان فعال‌سازی ثبت آمار</p>
        </div>

        <div class="grid gap-3 sm:grid-cols-3">
            <div class="admin-metric">
                <span class="admin-metric-label">بازدید امروز</span>
                <strong class="admin-metric-value">{{ number_format($analytics['today']) }}</strong>
                <span class="admin-metric-hint">تا این لحظه · ساعت ایران</span>
            </div>
            <div class="admin-metric">
                <span class="admin-metric-label">بازدید دیروز</span>
                <strong class="admin-metric-value">{{ number_format($analytics['yesterday']) }}</strong>
                <span class="admin-metric-hint">روز کامل گذشته</span>
            </div>
            <div class="admin-metric">
                <span class="admin-metric-label">کل بازدیدها</span>
                <strong class="admin-metric-value">{{ number_format($analytics['total']) }}</strong>
                <span class="admin-metric-hint">از آغاز ثبت بازدید</span>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-x-7 gap-y-2 rounded-xl bg-[#f1f5ee] px-4 py-3 text-sm text-[#385343]">
            <span>صفحه اصلی: <strong class="font-extrabold">{{ number_format($analytics['home']) }}</strong></span>
            <span>صفحات محصولات: <strong class="font-extrabold">{{ number_format($analytics['products']) }}</strong></span>
        </div>

        <div class="space-y-2">
            <h3 class="text-sm font-extrabold text-[#244535]">۵ محصول پربازدید</h3>
            <ol class="divide-y divide-[#e1e8dd]">
                @forelse ($analytics['topProducts'] as $product)
                    <li class="flex flex-wrap items-center justify-between gap-2 py-2.5 text-sm">
                        <span class="font-semibold text-[#244535]">{{ $product['title'] }}</span>
                        <strong class="whitespace-nowrap text-[#526c57]">{{ number_format($product['views']) }} بازدید</strong>
                    </li>
                @empty
                    <li class="py-3 text-sm text-[#617463]">هنوز بازدیدی برای محصولات ثبت نشده است.</li>
                @endforelse
            </ol>
        </div>
    </section>

    <section aria-labelledby="today-actions" class="space-y-3">
        <h2 id="today-actions" class="admin-section-title">نیازمند توجه</h2>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @can('payments.view')
                <a wire:navigate class="admin-metric" href="{{ route('admin.payments') }}">
                    <span class="admin-metric-label">رسیدهای در انتظار بررسی بانک</span>
                    <span class="admin-metric-value">{{ number_format($pendingReceiptCount) }}</span>
                    <span class="admin-metric-hint">تأیید فقط پس از تطبیق واریز</span>
                </a>
                <a wire:navigate class="admin-metric" href="{{ route('admin.payments.gateway') }}">
                    <span class="admin-metric-label">تراکنش‌های نامشخص زرین‌پال</span>
                    <span class="admin-metric-value">{{ number_format($unresolvedGatewayCount) }}</span>
                    <span class="admin-metric-hint">پرداخت مجدد تا تطبیق مجاز نیست</span>
                </a>
            @endcan
            <a wire:navigate class="admin-metric" href="{{ route('admin.notifications') }}">
                <span class="admin-metric-label">ثبت اعلان‌های ناموفق</span>
                <span class="admin-metric-value">{{ number_format($failedNotificationCount) }}</span>
                <span class="admin-metric-hint">بررسی صف و گزارش خطا</span>
            </a>
        </div>
    </section>

    <section class="admin-surface space-y-4" aria-labelledby="recent-orders">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 id="recent-orders" class="admin-section-title">آخرین سفارش‌ها</h2>
            <a wire:navigate class="admin-inline-link" href="{{ route('admin.orders') }}">همه سفارش‌ها</a>
        </div>
        <div class="divide-y divide-[#e1e8dd]">
            @forelse ($recentOrders as $order)
                <a wire:navigate href="{{ route('admin.orders.show', $order) }}" class="flex min-h-16 flex-wrap items-center justify-between gap-3 py-4 hover:bg-[#f6f8f3]">
                    <div class="space-y-1">
                        <p class="font-extrabold" dir="ltr">{{ $order->public_reference }}</p>
                        <p class="text-xs text-[#5a6e5d]">{{ $order->created_at->format('Y/m/d H:i') }} · {{ $order->recipient_name }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="admin-state admin-state-{{ \App\Support\AdminLabels::tone($order->order_status) }}">{{ \App\Support\AdminLabels::order($order->order_status) }}</span>
                        <strong class="admin-money text-sm">{{ \App\Support\Money::formatToman($order->total_irr) }} تومان</strong>
                    </div>
                </a>
            @empty
                <p class="py-7 text-sm text-[#617463]">هنوز سفارشی ثبت نشده است.</p>
            @endforelse
        </div>
    </section>

    @can('catalog.manage')
        <section class="admin-surface space-y-3" aria-labelledby="catalog-overview">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 id="catalog-overview" class="admin-section-title">نمای کلی کاتالوگ</h2>
                <a wire:navigate href="{{ route('admin.products') }}" class="admin-inline-link">مدیریت محصولات</a>
            </div>
            <div class="grid gap-3 sm:grid-cols-3">
                <p class="rounded-xl bg-[#f1f5ee] p-4 text-sm">دسته‌بندی‌ها <strong class="mt-2 block text-xl">{{ number_format($categoryCount) }}</strong></p>
                <p class="rounded-xl bg-[#f1f5ee] p-4 text-sm">همه محصولات <strong class="mt-2 block text-xl">{{ number_format($productCount) }}</strong></p>
                <p class="rounded-xl bg-[#f1f5ee] p-4 text-sm">محصولات منتشرشده <strong class="mt-2 block text-xl">{{ number_format($publishedCount) }}</strong></p>
            </div>
        </section>
    @endcan
</div>
