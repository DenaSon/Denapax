<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#122d25">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $title ?? 'فروشگاه دناپکس' }} | دناپکس</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/landing.css', 'resources/js/storefront.js'])
    @livewireStyles
</head>
<body class="landing store-shell min-h-screen antialiased">
    <a href="#store-main" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:right-3 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2">رفتن به محتوای اصلی</a>
    <header class="store-header">
        <nav class="landing-container store-header-inner flex flex-wrap items-center justify-between gap-3 py-3" aria-label="ناوبری فروشگاه">
            <a wire:navigate class="store-brand" href="{{ route('home') }}" aria-label="دناپکس، بازگشت به محصولات">
                <svg class="store-brand-mark" viewBox="0 0 70 48" fill="none" aria-hidden="true">
                    <path d="M3 40 26 8l15 22 9-13 17 23" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="m19 18 7-10 10 15-8-5-6 8" fill="currentColor"/>
                    <path d="M3 46h64" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <span>
                    <span class="store-brand-name">دناپکس</span>
                    <span class="store-brand-tagline">سوغات، از دل دنا</span>
                </span>
            </a>
            <div class="store-header-nav">
                <a wire:navigate href="{{ route('home') }}#products" class="store-header-link">محصولات</a>
                <a wire:navigate href="{{ route('cart.index') }}" class="store-header-link store-header-cart"
                   @if (request()->routeIs('cart.index')) aria-current="page" @endif>
                    سبد خرید
                    <livewire:store.cart-count />
                </a>
                @auth('customer')
                    <details class="store-account-menu">
                        <summary class="store-header-link" aria-label="بازکردن منوی حساب مشتری">
                            حساب من <span aria-hidden="true">⌄</span>
                        </summary>
                        <div class="store-account-dropdown">
                            <a wire:navigate href="{{ route('orders.index') }}">سفارش‌های من</a>
                            <a wire:navigate href="{{ route('addresses.index') }}">نشانی‌ها</a>
                            <a wire:navigate href="{{ route('customer.notifications.index') }}">پیام‌ها</a>
                            <form method="POST" action="{{ route('customer.logout') }}">
                                @csrf
                                <button type="submit">خروج از حساب</button>
                            </form>
                        </div>
                    </details>
                @else
                    <a wire:navigate href="{{ route('customer.login') }}" class="store-header-link">ورود</a>
                @endauth
            </div>
        </nav>
    </header>
    @if (request()->routeIs('cart.index', 'checkout.*', 'orders.confirmation'))
        @php
            $purchaseStep = request()->routeIs('cart.index') ? 1 : (request()->routeIs('checkout.*') ? 2 : 3);
        @endphp
        <nav class="store-steps" aria-label="مراحل خرید">
            <ol>
                <li data-state="{{ $purchaseStep === 1 ? 'active' : 'done' }}" @if ($purchaseStep === 1) aria-current="step" @endif>
                    <span class="store-steps-number">۱</span> سبد خرید
                </li>
                <li data-state="{{ $purchaseStep === 2 ? 'active' : ($purchaseStep > 2 ? 'done' : 'upcoming') }}" @if ($purchaseStep === 2) aria-current="step" @endif>
                    <span class="store-steps-number">۲</span> نشانی و ارسال
                </li>
                <li data-state="{{ $purchaseStep === 3 ? 'active' : 'upcoming' }}" @if ($purchaseStep === 3) aria-current="step" @endif>
                    <span class="store-steps-number">۳</span> پرداخت
                </li>
            </ol>
        </nav>
    @endif
    <main id="store-main" class="landing-container store-main mx-auto py-8 sm:py-12">
        @if (session('status'))
            <div role="status" class="store-success mb-5 rounded-xl p-4 text-sm leading-7">{{ session('status') }}</div>
        @endif
        @if (session('notice'))
            <div role="status" class="store-notice mb-5 rounded-xl p-4 text-sm leading-7">{{ session('notice') }}</div>
        @endif
        @if ($errors->any())
            <div role="alert" class="store-error-summary mb-5 rounded-xl p-4 text-sm leading-7" tabindex="-1">
                <p class="font-extrabold">برای ادامه، این موارد را بررسی کنید:</p>
                <ul class="mt-2 list-disc space-y-1 pr-6">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        {{ $slot }}
    </main>
    <footer class="store-footer landing-container mx-auto flex flex-wrap items-center justify-between gap-2 py-6 text-xs">
        <span>دناپکس · سوغات و خشکبار دنا</span>
        <a wire:navigate class="store-inline-link" href="{{ route('home') }}#products">بازگشت به فروشگاه</a>
    </footer>
    @livewireScripts
</body>
</html>
