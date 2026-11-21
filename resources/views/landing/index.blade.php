<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" sizes="any" href="{{ asset('images/denapax-mark.svg') }}">
    <meta name="theme-color" content="#19392f">
    <meta name="enamad" content="45504479" />
    <meta name="robots" content="index,follow,max-image-preview:large">
    <meta name="description" content="خرید سوغات دنا، خشکبار و محصولات محلی سی‌سخت در دناپکس. تصاویر، مشخصات و قیمت بسته‌های موجود را ببینید و سفارش خود را ثبت کنید.">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="fa_IR">
    <meta property="og:site_name" content="DenaPax">
    <meta property="og:title" content="دناپکس | خرید سوغات دنا، خشکبار و محصولات محلی سی‌سخت{{ $products->currentPage() > 1 ? ' - صفحه '.$products->currentPage() : '' }}">
    <meta property="og:description" content="محصولات محلی دنا و سی‌سخت را با تصاویر، مشخصات و قیمت بسته‌ها در فروشگاه دناپکس بررسی کنید.">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta name="twitter:card" content="summary">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="preload" href="{{ asset('images/dena-landscape.svg') }}" as="image" type="image/svg+xml">
    <title>دناپکس | خرید سوغات دنا، خشکبار و محصولات محلی سی‌سخت{{ $products->currentPage() > 1 ? ' - صفحه '.$products->currentPage() : '' }}</title>
    <script type="application/ld+json">@json($websiteSchema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>
    @vite(['resources/css/landing.css', 'resources/js/storefront.js'])
    @livewireStyles
</head>
<body class="landing landing-shell home-catalog antialiased" data-analytics-page="home" data-analytics-product-id="0" data-analytics-endpoint="{{ route('analytics.page-view') }}" x-data="landingPage()" x-on:keydown.escape.window="menuOpen && closeMenu(true)">
    <a class="sr-only focus:not-sr-only focus:fixed focus:z-50 focus:top-3 focus:right-3 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2" href="#products">رفتن به محصولات</a>

    <header class="landing-header sticky top-0 z-50 text-white"
             x-bind:class="headerCompact ? 'is-compact' : ''">
        <div class="landing-container landing-header-inner flex items-center justify-between gap-4 border-b border-white/20 md:gap-5"
             x-bind:class="headerCompact ? 'is-compact' : ''">
            <a wire:navigate href="{{ route('home') }}" aria-label="دناپکس، بازگشت به ابتدای صفحه" class="group inline-flex shrink-0 items-center gap-3" x-on:click="menuOpen = false">
                <img x-bind:class="headerCompact ? 'is-compact' : ''"
                     class="landing-header-mark transition-all duration-300 group-hover:-translate-y-1"
                     src="{{ asset('images/denapax-header-icon.svg') }}" width="512" height="512" alt="" aria-hidden="true"
                     decoding="async" fetchpriority="high">
                <span class="text-right">
                    <span x-bind:class="headerCompact ? 'is-compact' : ''"
                          class="landing-header-wordmark block font-bold leading-none tracking-tight" lang="en">DenaPax</span>
                    <span x-show="!headerCompact" x-cloak class="mt-1 block text-[10px] tracking-[.18em] text-white/65">از دل دنا</span>
                </span>
            </a>

            <nav class="hidden items-center gap-10 text-sm font-medium text-white/85 lg:flex" aria-label="فهرست اصلی">
                <a href="#products" class="transition hover:text-white">محصولات</a>
                <a href="#categories" class="transition hover:text-white">دسته‌بندی‌ها</a>
                <a href="#shopping-guide" class="transition hover:text-white">راهنمای خرید</a>
            </nav>

            <div class="flex items-center gap-2 lg:gap-4">
                <livewire:store.landing-header-cart />
                <button type="button" x-ref="menuToggle" x-bind:class="headerCompact ? 'is-compact' : ''"
                        class="landing-header-toggle lg:hidden inline-flex items-center justify-center rounded-full border border-white/40 text-white" x-on:click="toggleMenu()" x-bind:aria-expanded="menuOpen.toString()" x-bind:aria-label="menuOpen ? 'بستن منو' : 'باز کردن منو'" aria-controls="landing-mobile-nav">
                    <svg x-show="!menuOpen" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                    <svg x-show="menuOpen" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m5 5 14 14M19 5 5 19"/></svg>
                </button>
            </div>
        </div>
        <nav id="landing-mobile-nav" x-ref="mobileNav" x-show="menuOpen" x-cloak x-transition.opacity.duration.200ms x-on:keydown="onMobileNavKeydown($event)" x-on:click.outside="closeMenu()" aria-label="فهرست موبایل" class="landing-container mt-3 rounded-2xl border border-white/10 bg-[#18352d] p-5 shadow-2xl lg:hidden">
            <a x-on:click="closeMenu(true)" href="#products" class="block border-b border-white/15 py-3 text-sm">محصولات</a>
            <a x-on:click="closeMenu(true)" href="#categories" class="block border-b border-white/15 py-3 text-sm">دسته‌بندی‌ها</a>
            <a x-on:click="closeMenu(true)" href="#shopping-guide" class="block border-b border-white/15 py-3 text-sm">راهنمای خرید</a>
            <a wire:navigate x-on:click="closeMenu(true)" href="{{ route('cart.index') }}" class="mt-3 block rounded-xl bg-[#e8e8d7] px-4 py-3 text-center text-sm font-bold text-[#19392f]">مشاهده سبد خرید</a>
        </nav>
        <noscript>
            <nav class="landing-container mt-3 flex flex-wrap gap-5 rounded-2xl bg-[#18352d] p-5 text-sm lg:hidden" aria-label="فهرست موبایل بدون جاوااسکریپت">
                <a href="#products">محصولات</a>
                <a href="#categories">دسته‌بندی‌ها</a>
                <a href="#shopping-guide">راهنمای خرید</a>
            </nav>
        </noscript>
    </header>

    <main id="main-content">
        <section id="top" class="home-store-intro" aria-labelledby="hero-title">
            <div class="landing-container home-intro-inner">
                <div class="home-intro-copy">
                    <p class="home-intro-label">دناپکس · فروشگاه سوغات سی‌سخت</p>
                    <h1 id="hero-title">سوغات و خشکبار دنا</h1>
                    <p>خشکبار، میوه خشک و خوراکی‌های محلی دنا و سی‌سخت؛ با وزن، تصویر و قیمت مشخص برای انتخاب آسان‌تر.</p>
                </div>
                <a href="#products" class="home-intro-link">
                    مشاهده محصولات
                    <span aria-hidden="true">←</span>
                </a>
            </div>
        </section>

        <section id="products" class="landing-container home-products scroll-mt-24" aria-labelledby="products-title">
            <div class="home-products-heading">
                <div>
                    <p class="home-products-eyebrow">محصولات فروشگاه دناپکس</p>
                    <h2 id="products-title">انتخاب‌های دنا</h2>
                    <p>بسته‌های موجود را ببینید، وزن موردنظرتان را انتخاب کنید و به سبد خرید اضافه کنید.</p>
                </div>
                <span class="home-products-count">{{ $products->total() }} محصول قابل سفارش</span>
            </div>

            <form method="GET" action="{{ route('search') }}" role="search"
                  x-data="storeSearch" x-on:submit.prevent="submit($event)"
                  class="home-product-search mb-7 flex flex-wrap items-center gap-3">
                <label for="catalog-search" class="sr-only">جستجو در محصولات</label>
                <input id="catalog-search" name="q" type="search" maxlength="80"
                       placeholder="جستجو در خشکبار و سوغات دنا..."
                       class="landing-search-input min-w-0 flex-1 rounded-full border border-[#cbd3c3] bg-white/70 px-5 py-3 text-sm text-[#244534] outline-offset-2 sm:max-w-xl">
                <button type="submit" class="landing-search-button rounded-full bg-[#19392f] px-6 py-3 text-sm font-bold text-white">جستجو</button>
            </form>

            <nav id="categories" aria-label="دسته‌بندی محصولات" class="home-categories mb-8 flex flex-wrap items-center gap-2.5 md:mb-10">
                    <a wire:navigate href="{{ route('home') }}#products" aria-current="page"
                       class="home-category-all rounded-full px-5 py-2.5 text-xs font-semibold">
                        همه محصولات
                    </a>
                    @foreach ($categories as $category)
                        <a wire:navigate href="{{ route('categories.show', ['slug' => $category->slug]) }}"
                           class="home-category-link {{ $loop->index < 3 ? 'home-category-priority' : '' }} rounded-full px-5 py-2.5 text-xs font-semibold">
                            {{ $category->name }}
                        </a>
                    @endforeach
                </nav>

            @livewire('store.home-product-feed', ['startPage' => $products->currentPage()])
        </section>
    </main>

    <footer id="shopping-guide" class="home-footer" aria-label="راهنمای فروشگاه و درباره دناپکس">
        <div class="landing-container">
            <div class="home-footer-grid">
                <section aria-labelledby="footer-about-title" class="home-footer-about">
                    <p class="home-footer-kicker">از دل دنا</p>
                    <h2 id="footer-about-title">دناپکس؛ سوغات و خشکبار دنا</h2>
                    <p>دناپکس مجموعه‌ای از خشکبار، میوه‌های خشک و سوغات محلی منطقه دنا و شهر سی‌سخت را برای خرید آنلاین گرد هم آورده است. می‌توانید تصاویر محصولات، وزن بسته‌ها و قیمت هر تنوع را بررسی کنید و سفارش خود را با نشانی تحویل ثبت کنید.</p>
                </section>

                <nav aria-label="راهنمای خرید" class="home-footer-column">
                    <h2>راهنمای خرید</h2>
                    <ol class="home-footer-steps">
                        <li>محصول و وزن بسته را انتخاب کنید.</li>
                        <li>سبد خرید، هزینه ارسال و نشانی را بررسی کنید.</li>
                        <li>سفارش را ثبت و روش پرداخت را انتخاب کنید.</li>
                    </ol>
                    <a wire:navigate href="{{ route('cart.index') }}">سبد خرید و ثبت سفارش</a>
                </nav>

                <nav aria-label="دسترسی سریع" class="home-footer-column">
                    <h2>دسترسی سریع</h2>
                    <a href="#products">محصولات</a>
                    <a href="#categories">دسته‌بندی‌ها</a>
                    <a wire:navigate href="{{ route('search') }}">جستجوی محصولات</a>
                    <a wire:navigate href="{{ route('orders.index') }}">پیگیری سفارش‌ها</a>
                    <a wire:navigate href="{{ route('customer.login') }}">ورود به حساب مشتری</a>
                </nav>
            </div>
            <div class="home-footer-bottom">
                <span>دناپکس · سوغات سی‌سخت و محصولات محلی دنا</span>
                @include('partials.store-support-contact')
                <span>طراحی و توسعه:
                    <a href="https://t.me/m_asadix7" target="_blank" rel="noopener noreferrer">Mohammad Asadi</a>
                </span>
            </div>
        </div>
    </footer>

    <a href="#top" x-show="hasScrolled" x-cloak x-transition.opacity.duration.200ms
       class="fixed bottom-5 left-5 z-30 flex h-12 w-12 items-center justify-center rounded-full bg-[#19392f] text-white shadow-xl ring-1 ring-white/20 md:bottom-8 md:left-8"
       aria-label="بازگشت به بالای صفحه">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 19V5m-6 6 6-6 6 6"/></svg>
    </a>

    <livewire:store.network-status />
    @livewireScripts
</body>
</html>
