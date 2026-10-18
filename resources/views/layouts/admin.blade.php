<!DOCTYPE html>
<html lang="fa" dir="rtl" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>مدیریت دنا | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-base-200 text-base-content">
    <header class="navbar border-b border-base-300 bg-base-100 px-6">
        <div class="flex-1"><a href="{{ route('admin.dashboard') }}" class="text-xl font-bold">دنا · پنل مدیریت</a></div>
        <div class="flex items-center gap-3">
            <span class="text-sm">{{ auth()->user()->name }}</span>
            <form action="{{ url('/logout') }}" method="POST">
                @csrf
                <x-button type="submit" label="خروج" class="btn-ghost btn-sm" />
            </form>
        </div>
    </header>
    <div class="mx-auto flex max-w-7xl flex-col gap-6 p-5 md:flex-row">
        <aside class="w-full shrink-0 rounded-xl bg-base-100 p-4 md:w-56">
            <nav class="menu w-full gap-2">
                <li><a href="{{ route('admin.dashboard') }}">داشبورد</a></li>
                @can('catalog.manage')
                    <li><a href="{{ route('admin.categories') }}">دسته‌بندی‌ها</a></li>
                    <li><a href="{{ route('admin.products') }}">محصولات</a></li>
                @endcan
                @can('orders.view')
                    <li><a href="{{ route('admin.orders') }}">سفارش‌ها</a></li>
                    <li><a href="{{ route('admin.notifications') }}">گزارش اعلان‌ها</a></li>
                @endcan
                @can('payments.view')
                    <li><a href="{{ route('admin.payments') }}">رسیدهای پرداخت</a></li>
                @endcan
                @can('shipping.manage')
                    <li><a href="{{ route('admin.shipping') }}">هزینه ارسال</a></li>
                @endcan
                @can('settings.manage')
                    <li><a href="{{ route('admin.settings') }}">تنظیمات فروشگاه</a></li>
                @endcan
            </nav>
        </aside>
        <main class="min-w-0 flex-1 space-y-6">
            @if (session('status'))
                <div role="status" class="alert alert-success">{{ session('status') }}</div>
            @endif
            {{ $slot }}
        </main>
    </div>
    @livewireScripts
</body>
</html>
