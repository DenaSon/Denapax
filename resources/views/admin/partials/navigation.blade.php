<nav class="admin-nav-list" aria-label="بخش‌های مدیریت">
    <p class="admin-nav-section">نمای کلی</p>
    <a wire:navigate class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" href="{{ route('admin.dashboard') }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>◫ <span>داشبورد</span></a>
    @can('orders.view')
        <p class="admin-nav-section">عملیات فروش</p>
        <a wire:navigate class="admin-nav-link {{ request()->routeIs('admin.orders*') ? 'is-active' : '' }}" href="{{ route('admin.orders') }}" @if(request()->routeIs('admin.orders*')) aria-current="page" @endif>▤ <span>سفارش‌ها</span></a>
    @endcan
    @can('payments.view')
        <a wire:navigate class="admin-nav-link {{ request()->routeIs('admin.payments') ? 'is-active' : '' }}" href="{{ route('admin.payments') }}" @if(request()->routeIs('admin.payments')) aria-current="page" @endif>▣ <span>رسیدهای پرداخت</span></a>
        <a wire:navigate class="admin-nav-link {{ request()->routeIs('admin.payments.gateway') ? 'is-active' : '' }}" href="{{ route('admin.payments.gateway') }}" @if(request()->routeIs('admin.payments.gateway')) aria-current="page" @endif>⟲ <span>تراکنش‌های زرین‌پال</span></a>
    @endcan
    @can('orders.view')
        <a wire:navigate class="admin-nav-link {{ request()->routeIs('admin.notifications') ? 'is-active' : '' }}" href="{{ route('admin.notifications') }}" @if(request()->routeIs('admin.notifications')) aria-current="page" @endif>◌ <span>گزارش اعلان‌ها</span></a>
    @endcan
    @can('catalog.manage')
        <p class="admin-nav-section">فروشگاه</p>
        <a wire:navigate class="admin-nav-link {{ request()->routeIs('admin.products*') ? 'is-active' : '' }}" href="{{ route('admin.products') }}" @if(request()->routeIs('admin.products*')) aria-current="page" @endif>▦ <span>محصولات</span></a>
        <a wire:navigate class="admin-nav-link {{ request()->routeIs('admin.categories') ? 'is-active' : '' }}" href="{{ route('admin.categories') }}" @if(request()->routeIs('admin.categories')) aria-current="page" @endif>▧ <span>دسته‌بندی‌ها</span></a>
    @endcan
    @can('shipping.manage')
        <a wire:navigate class="admin-nav-link {{ request()->routeIs('admin.shipping') ? 'is-active' : '' }}" href="{{ route('admin.shipping') }}" @if(request()->routeIs('admin.shipping')) aria-current="page" @endif>⌁ <span>هزینه ارسال</span></a>
    @endcan
    @can('settings.manage')
        <a wire:navigate class="admin-nav-link {{ request()->routeIs('admin.settings') ? 'is-active' : '' }}" href="{{ route('admin.settings') }}" @if(request()->routeIs('admin.settings')) aria-current="page" @endif>⚙ <span>تنظیمات فروشگاه</span></a>
    @endcan
</nav>
