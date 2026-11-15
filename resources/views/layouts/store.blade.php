<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $title ?? 'فروشگاه دناپکس' }} | دناپکس</title>
    @vite(['resources/css/landing.css'])
</head>
<body class="landing landing-shell min-h-screen bg-[#f4f1e6] text-[#19392f] antialiased">
    <header class="bg-[#19392f] text-white">
        <nav class="landing-container flex flex-wrap items-center justify-between gap-4 py-6" aria-label="ناوبری فروشگاه">
            <a class="text-xl font-bold" href="{{ route('home') }}">دناپکس · از دل دنا</a>
            <div class="flex items-center gap-5 text-sm">
                <a href="{{ route('home') }}#products">محصولات</a>
                <a href="{{ route('cart.index') }}">سبد خرید</a>
                @auth('customer')
                    <a href="{{ route('orders.index') }}">سفارش‌های من</a>
                    <a href="{{ route('addresses.index') }}">نشانی‌ها</a>
                    <a href="{{ route('customer.notifications.index') }}">پیام‌ها</a>
                @endauth
                @auth('customer')
                    <form action="{{ route('customer.logout') }}" method="POST">@csrf <button type="submit">خروج از حساب</button></form>
                @else
                    <a href="{{ route('customer.login') }}">ورود مشتری</a>
                @endauth
            </div>
        </nav>
    </header>
    <main class="landing-container mx-auto max-w-4xl py-12">
        @if (session('status'))
            <p role="status" class="mb-5 rounded-xl bg-[#d9eadb] p-4">{{ session('status') }}</p>
        @endif
        @if (session('notice'))
            <p role="status" class="mb-5 rounded-xl bg-[#f8eddd] p-4">{{ session('notice') }}</p>
        @endif
        @if ($errors->any())
            <div role="alert" class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">
                <ul class="list-disc space-y-2 pr-6">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        {{ $slot }}
    </main>
</body>
</html>
