<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#19392f">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $title ?? 'فروشگاه دناپکس' }} | دناپکس</title>
    @vite(['resources/css/landing.css'])
</head>
<body class="landing landing-shell min-h-screen bg-[#f4f1e6] text-[#19392f] antialiased">
    <a href="#store-main" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:right-3 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2">رفتن به محتوای اصلی</a>
    <header class="bg-[#19392f] text-white">
        <nav class="landing-container flex flex-wrap items-center justify-between gap-3 py-4 sm:py-5" aria-label="ناوبری فروشگاه">
            <a class="text-lg font-extrabold sm:text-xl" href="{{ route('home') }}">دناپکس · از دل دنا</a>
            <div class="flex items-center gap-2 text-sm sm:gap-4">
                <a href="{{ route('home') }}#products" class="inline-flex min-h-11 items-center px-1">محصولات</a>
                <a href="{{ route('cart.index') }}"
                   class="inline-flex min-h-11 items-center rounded-full border border-white/50 px-4 font-semibold">سبد خرید</a>
                @auth('customer')
                    <details class="relative">
                        <summary class="flex min-h-11 cursor-pointer list-none items-center rounded-full border border-white/40 px-4 font-semibold">حساب من</summary>
                        <div class="absolute left-0 z-40 mt-2 min-w-44 rounded-2xl border border-[#e2e7dd] bg-white p-2 text-[#19392f] shadow-xl">
                            <a class="flex min-h-11 items-center rounded-lg px-3 hover:bg-[#eef2e9]" href="{{ route('orders.index') }}">سفارش‌های من</a>
                            <a class="flex min-h-11 items-center rounded-lg px-3 hover:bg-[#eef2e9]" href="{{ route('addresses.index') }}">نشانی‌ها</a>
                            <a class="flex min-h-11 items-center rounded-lg px-3 hover:bg-[#eef2e9]" href="{{ route('customer.notifications.index') }}">پیام‌ها</a>
                            <form method="POST" action="{{ route('customer.logout') }}">
                                @csrf
                                <button type="submit" class="min-h-11 w-full rounded-lg px-3 text-right hover:bg-[#eef2e9]">خروج</button>
                            </form>
                        </div>
                    </details>
                @else
                    <a href="{{ route('customer.login') }}" class="inline-flex min-h-11 items-center px-1">ورود</a>
                @endauth
            </div>
        </nav>
    </header>
    <main id="store-main" class="landing-container mx-auto max-w-4xl py-6 pb-16 sm:py-10">
        @if (session('status'))
            <p role="status" class="mb-5 rounded-xl bg-[#d9eadb] p-4 text-sm leading-7">{{ session('status') }}</p>
        @endif
        @if (session('notice'))
            <p role="status" class="mb-5 rounded-xl bg-[#f8eddd] p-4 text-sm leading-7">{{ session('notice') }}</p>
        @endif
        @if ($errors->any())
            <div role="alert" class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm leading-7 text-red-800">
                <p class="font-bold">لطفاً این موارد را بررسی کنید:</p>
                <ul class="mt-2 list-disc space-y-1 pr-6">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        {{ $slot }}
    </main>
</body>
</html>
