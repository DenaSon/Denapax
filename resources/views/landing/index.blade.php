<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#19392f">
    <meta name="robots" content="index,follow,max-image-preview:large">
    <meta name="description" content="دناپکس؛ آشنایی با سوغات، خشکبار و محصولات محلی دنا. محصولات را ببینید و به سبد خرید اضافه کنید.">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="fa_IR">
    <meta property="og:site_name" content="DenaPax">
    <meta property="og:title" content="دناپکس | از دل دنا">
    <meta property="og:description" content="روایتی از طعم‌های محلی دنا؛ معرفی محصولات و خرید از وب‌سایت.">
    <meta property="og:url" content="{{ route('home') }}">
    <meta name="twitter:card" content="summary">
    <link rel="canonical" href="{{ route('home') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://upload.wikimedia.org" crossorigin>
    <link rel="preload" href="https://upload.wikimedia.org/wikipedia/commons/3/35/Dena_mountains_%DA%A9%D9%88%D9%87%D9%87%D8%A7%DB%8C_%D8%AF%D9%86%D8%A7_%DA%A9%D9%87%DA%AF%DB%8C%D9%84%D9%88%DB%8C%D9%87_%D8%A8%D9%88%DB%8C%D8%B1_%D8%A7%D8%AD%D9%85%D8%AF_-_panoramio.jpg" as="image">
    <title>دناپکس | از دل دنا</title>
    <script type="application/ld+json">@json($websiteSchema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>
    @vite(['resources/css/landing.css', 'resources/js/storefront.js'])
    @livewireStyles
</head>
<body class="landing landing-shell antialiased" x-data="landingPage()" x-on:keydown.escape.window="menuOpen && closeMenu(true)" x-on:click="handleCatalogClick($event)" x-on:cart-added.window="cartSubmitting = false; cartNotice = 'محصول به سبد خرید اضافه شد.'; closeProduct()" x-on:cart-add-failed.window="cartSubmitting = false; cartError = $event.detail.message">
    <div class="landing-progress" x-cloak x-bind:style="'--landing-progress: ' + scrollProgress" aria-hidden="true"></div>
    <nav class="landing-chapter-rail" aria-label="بخش‌های صفحه" x-cloak>
        <a href="#products" x-bind:aria-current="activeSection === 'products' ? 'location' : null" aria-label="بخش اول: محصولات" title="محصولات">01</a>
        <a href="#story" x-bind:aria-current="activeSection === 'story' ? 'location' : null" aria-label="بخش دوم: روایت دنا" title="روایت دنا">02</a>
        <a href="#order" x-bind:aria-current="activeSection === 'order' ? 'location' : null" aria-label="بخش سوم: سفارش" title="سفارش">03</a>
    </nav>
    <div x-show="cartNotice" x-cloak role="status" class="fixed bottom-5 right-5 z-[90] flex max-w-[min(92vw,420px)] items-center gap-4 rounded-2xl border border-[#8ba991] bg-[#f2f8ee] px-5 py-3 text-sm font-bold text-[#1a4930] shadow-2xl">
        <span x-text="cartNotice"></span>
        <button type="button" x-on:click="cartNotice = ''" class="rounded-full border border-[#a4bda6] px-2 py-1" aria-label="بستن اعلان">×</button>
    </div>
    <a class="sr-only focus:not-sr-only focus:fixed focus:z-50 focus:top-3 focus:right-3 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2" href="#products">رفتن به محصولات</a>

    <header class="absolute inset-x-0 top-0 z-30 text-white">
        <div class="landing-container flex h-22 items-center justify-between gap-5 border-b border-white/20 md:h-26">
            <a wire:navigate href="{{ route('home') }}" aria-label="دناپکس، بازگشت به ابتدای صفحه" class="group inline-flex shrink-0 items-center gap-3" x-on:click="menuOpen = false">
                <svg class="h-10 w-12 transition-transform duration-500 group-hover:-translate-y-1" viewBox="0 0 70 48" fill="none" aria-hidden="true">
                    <path d="M3 40 26 8l15 22 9-13 17 23" stroke="#E6E8D4" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="m19 18 7-10 10 15-8-5-6 8" fill="#E6E8D4"/>
                    <path d="M3 46h64" stroke="#E6E8D4" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <span class="text-right">
                    <span class="block text-lg font-bold leading-none tracking-tight md:text-xl" lang="en">DenaPax</span>
                    <span class="mt-1 block text-[10px] tracking-[.18em] text-white/65">از دل دنا</span>
                </span>
            </a>

            <nav class="hidden items-center gap-10 text-sm font-medium text-white/85 lg:flex" aria-label="فهرست اصلی">
                <a href="#products" x-bind:aria-current="activeSection === 'products' ? 'location' : null" class="transition hover:text-white">محصولات</a>
                <a href="#story" x-bind:aria-current="activeSection === 'story' ? 'location' : null" class="transition hover:text-white">روایت دنا</a>
                <a href="#order" x-bind:aria-current="activeSection === 'order' ? 'location' : null" class="transition hover:text-white">سفارش</a>
            </nav>

            <div class="hidden items-center gap-4 lg:flex">
                <span class="text-xs tracking-[.04em] text-white/75">سوغات و خشکبار دنا</span>
                <a wire:navigate class="landing-button border border-white/60 text-sm hover:bg-white hover:text-forest" href="{{ route('cart.index') }}">
                    سبد خرید
                    <livewire:store.cart-count />
                </a>
            </div>

            <div class="flex items-center gap-2 lg:hidden">
                <a wire:navigate href="{{ route('cart.index') }}" class="landing-mobile-cart" aria-label="سبد خرید{{ $cartQuantity > 0 ? '، '.$cartQuantity.' قلم کالا' : '' }}">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 5h2l2.2 10.5h9.6L20 8H7M10 20h.01M17 20h.01" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <livewire:store.cart-count />
                </a>
                <button type="button" x-ref="menuToggle" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/40 text-white" x-on:click="toggleMenu()" x-bind:aria-expanded="menuOpen.toString()" x-bind:aria-label="menuOpen ? 'بستن منو' : 'باز کردن منو'" aria-controls="landing-mobile-nav">
                    <svg x-show="!menuOpen" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                    <svg x-show="menuOpen" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m5 5 14 14M19 5 5 19"/></svg>
                </button>
            </div>
        </div>
        <nav id="landing-mobile-nav" x-ref="mobileNav" x-show="menuOpen" x-cloak x-transition.opacity.duration.200ms x-on:keydown="onMobileNavKeydown($event)" x-on:click.outside="closeMenu()" aria-label="فهرست موبایل" class="landing-container mt-3 rounded-2xl border border-white/10 bg-[#18352d] p-5 shadow-2xl lg:hidden">
            <a x-on:click="closeMenu(true)" href="#products" class="block border-b border-white/15 py-3 text-sm">محصولات</a>
            <a x-on:click="closeMenu(true)" href="#story" class="block border-b border-white/15 py-3 text-sm">روایت دنا</a>
            <a x-on:click="closeMenu(true)" href="#order" class="block border-b border-white/15 py-3 text-sm">سفارش</a>
            <a wire:navigate x-on:click="closeMenu(true)" href="{{ route('cart.index') }}" class="mt-3 block rounded-xl bg-[#e8e8d7] px-4 py-3 text-center text-sm font-bold text-[#19392f]">مشاهده سبد خرید{{ $cartQuantity > 0 ? ' ('.$cartQuantity.')' : '' }}</a>
        </nav>
        <noscript>
            <nav class="landing-container mt-3 flex flex-wrap gap-5 rounded-2xl bg-[#18352d] p-5 text-sm lg:hidden" aria-label="فهرست موبایل بدون جاوااسکریپت">
                <a href="#products">محصولات</a>
                <a href="#story">روایت دنا</a>
                <a href="#order">سفارش</a>
            </nav>
        </noscript>
    </header>

    <main>
        <section id="top" data-chapter class="landing-hero text-white" aria-labelledby="hero-title" x-on:pointermove="onHeroMove($event)" x-on:pointerleave="resetHero()">
            <div x-ref="landscape" class="landing-landscape" aria-hidden="true"></div>
            <div class="landing-hero-glow" aria-hidden="true"></div>
            <div class="landing-container relative flex min-h-[560px] flex-col items-center justify-center pb-19 pt-32 text-center md:min-h-[630px] md:pb-20 md:pt-36">
                <div class="absolute left-0 top-36 hidden flex-col items-end gap-3 lg:flex" aria-hidden="true">
                    <span class="text-xs tracking-[.32em] text-white/65" lang="en">THE DENA COLLECTION</span>
                    <span class="h-32 w-px bg-white/40"></span>
                    <span class="text-xs text-white/60">DENA / ORIGIN</span>
                </div>
                <div class="mx-auto flex max-w-[760px] flex-col items-center" data-reveal>
                    <p class="landing-label mb-7 flex items-center justify-center gap-3 text-[#e3d4b7]">
                        <span class="h-px w-8 bg-[#ddc6a1]"></span>
                        سوغات و خشکبار از سرزمین دنا
                    </p>
                    <h1 id="hero-title" class="landing-display text-[clamp(4.25rem,9vw,8.3rem)]">
                        از دل
                        <span class="block landing-outline">دنا</span>
                    </h1>
                    <p class="mt-7 max-w-[460px] text-[15px] leading-8 text-white/85 md:mt-8 md:text-base md:leading-9">
                        محصولاتی از دنا و نواحی پیرامونش؛ با مشخصات، وزن و قیمت روشن برای انتخابی ساده‌تر.
                    </p>
                    <div class="mt-8 flex flex-wrap items-center justify-center gap-5 md:mt-10">
                        <a href="#products" class="landing-button bg-[#e7e3d1] text-[#19392f] shadow-xl shadow-black/10 hover:bg-white">
                            محصولات را ببینید
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m5 5 14 14M5 19h14V5"/></svg>
                        </a>
                        <a href="#story" class="landing-hero-secondary text-sm font-semibold">آشنایی با دنا</a>
                    </div>
                </div>
                <div class="absolute bottom-6 right-0 left-0 flex items-end justify-between text-[11px] text-white/70 md:bottom-8">
                    <div class="flex items-center gap-4">
                        <span class="inline-block h-7 w-px bg-white/60"></span>
                        <span>برای دیدن محصولات اسکرول کنید</span>
                    </div>
                    <span class="tracking-[.25em]" lang="en">DENAPAX — 01</span>
                </div>
            </div>
        </section>

        <section class="landing-assurance" aria-label="ویژگی‌های خرید از دناپکس">
            <div class="landing-container landing-assurance-grid">
                <div class="landing-assurance-item">
                    <span class="landing-assurance-icon" aria-hidden="true">✦</span>
                    <span><strong>برگرفته از اقلیم دنا</strong><small>خشکبار و سوغات محلی</small></span>
                </div>
                <div class="landing-assurance-item">
                    <span class="landing-assurance-icon" aria-hidden="true">◎</span>
                    <span><strong>انتخاب آگاهانه</strong><small>وزن، مشخصات و قیمت به تومان</small></span>
                </div>
                <div class="landing-assurance-item">
                    <span class="landing-assurance-icon" aria-hidden="true">↗</span>
                    <span><strong>سفارش مستقیم</strong><small>سبد خرید، نشانی و تأیید پرداخت</small></span>
                </div>
            </div>
        </section>

        <section id="products" data-chapter class="landing-container scroll-mt-12 pt-14 pb-20 md:pt-19 md:pb-28" x-bind:aria-busy="catalogLoading.toString()" aria-labelledby="products-title">
            <div class="mb-10 flex flex-col justify-between gap-6 md:mb-14 md:flex-row md:items-end">
                <div data-reveal>
                    <div class="mb-4 flex items-center gap-3 text-xs font-semibold tracking-widest text-clay">
                        <span class="h-px w-8 bg-clay"></span>
                        THE COLLECTION / 01
                    </div>
                    <h2 id="products-title" class="landing-display text-[clamp(2.9rem,6.2vw,6.1rem)]">
                        انتخاب‌های <span class="landing-outline-dark">دنا</span>
                    </h2>
                    <p class="mt-5 text-sm leading-8 text-[#718276]">خشکبار و سوغات دنا را با تصویر، وزن و قیمت مشخص ببینید و انتخاب کنید.</p>
                </div>
                <span class="text-xs text-[#718276]" data-reveal>
                    {{ $products->total() }} محصول
                </span>
            </div>

            <form method="GET" action="{{ route('home') }}#products" role="search" class="mb-7 flex flex-wrap items-center gap-3" x-on:submit.prevent="submitCatalogSearch($event)">
                @if ($selectedCategory)
                    <input type="hidden" name="category" value="{{ $selectedCategory }}">
                @endif
                <label for="catalog-search" class="sr-only">جستجو در محصولات</label>
                <input id="catalog-search" name="q" type="search" maxlength="80" value="{{ $search }}"
                       placeholder="جستجو در سوغات و خشکبار دنا..." x-on:input.debounce.450ms="submitCatalogSearch($event)"
                       class="landing-search-input min-w-0 flex-1 rounded-full border border-[#cbd3c3] bg-white/70 px-5 py-3 text-sm text-[#244534] outline-offset-2 sm:max-w-xl">
                <button type="submit" class="landing-search-button rounded-full bg-[#19392f] px-6 py-3 text-sm font-bold text-white">جستجو</button>
                @if ($search !== '')
                    <a wire:navigate href="{{ route('home', $selectedCategory ? ['category' => $selectedCategory] : []) }}#products" class="text-xs font-semibold underline underline-offset-4">پاک‌کردن جستجو</a>
                @endif
            </form>

            @if ($categories->isNotEmpty())
                <nav aria-label="فیلتر دسته‌بندی محصولات" class="mb-8 flex flex-wrap items-center gap-2.5 md:mb-11">
                    <a wire:navigate data-catalog-link href="{{ route('home', $search !== '' ? ['q' => $search] : []) }}#products" aria-current="{{ $selectedCategory === null ? 'page' : 'false' }}"
                        class="{{ $selectedCategory === null ? 'bg-[#19392f] text-white' : 'border border-[#c9cec0] bg-white/45 text-[#3e604d] hover:border-[#3e604d]' }} rounded-full px-5 py-2.5 text-xs font-semibold transition-colors">
                        همه محصولات
                    </a>
                    @foreach ($categories as $category)
                        <a wire:navigate data-catalog-link href="{{ route('home', ['category' => $category->slug] + ($search !== '' ? ['q' => $search] : [])) }}#products" aria-current="{{ $selectedCategory === $category->slug ? 'page' : 'false' }}"
                            class="{{ $selectedCategory === $category->slug ? 'bg-[#19392f] text-white' : 'border border-[#c9cec0] bg-white/45 text-[#3e604d] hover:border-[#3e604d]' }} rounded-full px-5 py-2.5 text-xs font-semibold transition-colors">
                            {{ $category->name }}
                        </a>
                    @endforeach
                </nav>
            @endif

            <p class="mb-5 text-center text-xs text-[#4d7055]" x-show="catalogLoading" x-cloak role="status" aria-live="polite">در حال بارگذاری محصولات…</p>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 lg:gap-7" aria-live="polite">
                @forelse ($products as $product)
                    @php
                        // The newest database row is not implicitly a featured product.
                        $cover = $product->getFirstMedia('gallery');
                        if (! $cover) {
                            foreach ($product->variants as $variant) {
                                $cover = $variant->getFirstMedia('variant-image');
                                if ($cover) {
                                    break;
                                }
                            }
                        }
                        $startingPrice = (int) $product->variants->min('price_irr');
                    @endphp
                    <article class="product-card group relative overflow-hidden bg-[#ebe9dd]" data-reveal data-delay="{{ $loop->index % 3 }}">
                        <a wire:navigate href="{{ route('products.show', ['slug' => $product->slug]) }}"
                           x-on:click="openProduct({{ $product->id }}, $event)"
                           aria-haspopup="dialog" aria-controls="denapax-product-preview"
                           class="block h-full" aria-label="مشاهده جزئیات {{ $product->title }}">
                            <div class="product-photo relative overflow-hidden bg-[#c8cbb6]">
                                @if ($cover)
                                    <img class="product-card-image block w-full object-cover" src="{{ $cover->getUrl() }}"
                                         width="800" height="700" loading="lazy" decoding="async" alt="تصویر {{ $product->title }}">
                                @else
                                    <div class="product-card-image organic-lines relative flex items-center justify-center bg-[#cdd1bd]">
                                        <svg class="h-20 w-20 text-[#4b6f5a]/70" viewBox="0 0 90 90" fill="none" aria-hidden="true">
                                            <path d="M8 68 32 24l15 25 12-17 23 36" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M13 74h65M28 34l7 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        </svg>
                                    </div>
                                @endif
                                <span class="absolute top-5 right-5 rounded-full border border-white/60 bg-[#f3f0e4]/90 px-3 py-1.5 text-[11px] font-medium text-[#254736] backdrop-blur-sm">{{ $product->category->name }}</span>
                            </div>
                            <div class="flex items-end justify-between gap-3 p-5 md:p-7">
                                <div class="min-w-0">
                                    <h3 class="text-lg font-bold text-[#234635] md:text-xl">{{ $product->title }}</h3>
                                    @if ($product->summary)
                                        <p class="mt-2 line-clamp-2 text-xs leading-7 text-[#637366] md:text-sm">{{ $product->summary }}</p>
                                    @endif
                                    <p class="mt-4 text-sm font-semibold text-[#375642]">
                                        <span class="font-normal text-[#778677]">از</span>
                                        <span dir="ltr" class="inline-block">{{ \App\Support\Money::formatToman($startingPrice) }}</span>
                                        تومان
                                    </p>
                                </div>
                                <span class="product-card-action flex shrink-0 items-center gap-2 text-xs font-bold text-[#234635]" aria-hidden="true">
                                    مشاهده
                                    <span class="product-arrow flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-[#91a08d] text-[#234635]">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                                    </span>
                                </span>
                            </div>
                        </a>
                    </article>
                @empty
                    <div class="col-span-full organic-lines rounded-3xl border border-[#d6d9cc] bg-[#e9eadb] p-12 text-center md:p-24">
                        <p class="mb-4 text-sm font-semibold text-[#71816c]">COLLECTION / 00</p>
                        <h3 class="text-2xl font-bold text-[#254736] md:text-3xl">محصولی برای نمایش پیدا نشد</h3>
                        <p class="mt-4 text-sm leading-8 text-[#637366]">محصولات منتشرشده و قابل سفارش به‌صورت خودکار در این بخش نمایش داده می‌شوند.</p>
                        @if ($selectedCategory || $search !== '')
                            <a wire:navigate href="{{ route('home') }}#products" class="mt-6 inline-flex text-sm font-bold underline underline-offset-8">نمایش همه محصولات</a>
                        @endif
                    </div>
                @endforelse
            </div>

            @if ($products->hasPages())
                <nav class="mt-12 flex items-center justify-between border-t border-[#c9cec0] pt-7 text-sm font-semibold" aria-label="صفحات محصولات">
                    @if ($products->previousPageUrl())
                        <a class="transition hover:text-clay" data-catalog-link href="{{ $products->previousPageUrl() }}#products">صفحه قبل ←</a>
                    @else
                        <span class="text-[#9da99c]">صفحه قبل</span>
                    @endif
                    <span class="text-xs text-[#718276]">صفحه {{ $products->currentPage() }} از {{ $products->lastPage() }}</span>
                    @if ($products->nextPageUrl())
                        <a class="transition hover:text-clay" data-catalog-link href="{{ $products->nextPageUrl() }}#products">→ صفحه بعد</a>
                    @else
                        <span class="text-[#9da99c]">صفحه بعد</span>
                    @endif
                </nav>
            @endif
        </section>

        <section id="story" data-chapter class="relative overflow-hidden bg-[#e2e5d5] py-23 md:py-36" aria-labelledby="story-title">
            <div class="pointer-events-none absolute inset-y-0 left-0 w-full opacity-60 organic-lines lg:w-[65%]" aria-hidden="true"></div>
            <svg data-topography class="topographic-signature" viewBox="0 0 700 560" fill="none" aria-hidden="true">
                <path pathLength="1" d="M-60 80C30 10 130 20 220 75s185 68 280 3 175-75 265-10"/>
                <path pathLength="1" d="M-60 125C40 48 131 65 217 118s185 60 289 0 181-57 256 10"/>
                <path pathLength="1" d="M-55 175C40 108 129 119 215 162s191 65 287 5 169-46 263 29"/>
                <path pathLength="1" d="M-45 223C45 157 130 162 223 208s183 51 279 3 182-36 263 47"/>
                <path pathLength="1" d="M-35 271C50 212 139 214 226 255s177 45 276 7 177-21 263 65"/>
            </svg>
            <div class="landing-container relative grid items-center gap-16 lg:grid-cols-2">
                <div data-reveal>
                    <p class="landing-label mb-6 text-[#8b6e54]">FROM THE LAND / 02</p>
                    <h2 id="story-title" class="landing-display text-[clamp(3rem,5.9vw,6.7rem)]">
                        یک نام،
                        <span class="block landing-outline-dark">یک اقلیم</span>
                    </h2>
                    <p class="mt-8 max-w-[550px] text-base leading-10 text-[#536956]">
                        کوهستان دنا و شهرها و روستاهای پیرامون آن، مجموعه‌ای از خوراکی‌ها و شیوه‌های تهیه محلی را در خود جای داده‌اند. در دناپکس می‌توانید بخشی از این محصولات را بشناسید و برای خرید انتخاب کنید.
                    </p>
                    <div class="mt-9 flex items-center gap-4 text-xs font-bold tracking-wider text-[#536956]">
                        <span class="h-px w-10 bg-[#536956]"></span>
                        الهام‌گرفته از طبیعت دنا
                    </div>
                </div>
                <div class="landing-story-panel" data-reveal data-delay="1">
                    <div class="landing-story-image" role="img" aria-label="نمایی از رشته‌کوه دنا"></div>
                    <div class="landing-story-caption">
                        <span class="landing-story-caption-kicker" lang="en">THE DENA LANDSCAPE</span>
                        <span class="landing-story-caption-title">از کوهستان دنا</span>
                        <span class="landing-story-caption-detail">طبیعت و زندگی محلی</span>
                    </div>
                </div>
            </div>
        </section>

        <section id="order" data-chapter class="landing-order-section bg-[#17382d] text-[#f3f1e5]" aria-labelledby="order-title">
            <div class="landing-container landing-order-content">
                <div>
                    <p class="landing-label mb-3 text-[#c3d6be]">ORDER / 03</p>
                    <h2 id="order-title" class="text-2xl font-extrabold leading-relaxed md:text-3xl">از انتخاب محصول تا ثبت سفارش</h2>
                    <p class="mt-2 max-w-[580px] text-sm leading-8 text-white/75">محصولات دلخواهتان را انتخاب کنید؛ سپس نشانی، هزینه ارسال و روش پرداخت را در مراحل بعد ببینید.</p>
                </div>
                <a wire:navigate href="{{ route('cart.index') }}" class="landing-button landing-order-button bg-[#e8e8d7] text-[#183b2e] hover:bg-white">
                    مشاهده سبد خرید
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                </a>
            </div>
        </section>
    </main>

    <footer class="bg-[#122e26] py-10 text-white/75 md:py-13">
        <div class="landing-container flex flex-col justify-between gap-8 border-b border-white/15 pb-9 md:flex-row md:items-center">
            <div>
                <span class="block text-2xl font-bold tracking-tight text-white" lang="en">DenaPax.</span>
                <p class="mt-2 text-xs">سوغات و خشکبار؛ از دل دنا</p>
            </div>
            <nav aria-label="پیوندهای پایین صفحه" class="flex flex-wrap gap-6 text-sm">
                <a href="#products" class="hover:text-white">محصولات</a>
                <a href="#story" class="hover:text-white">روایت دنا</a>
                <a href="#order" class="hover:text-white">ثبت سفارش</a>
            </nav>
        </div>
        <div class="landing-container mt-7 flex flex-wrap justify-between gap-4 text-[11px] text-white/50">
            <span>دناپکس — معرفی محصولات محلی و خشکبار</span>
            <a href="https://commons.wikimedia.org/wiki/File:Dena_mountains_%DA%A9%D9%88%D9%87%D9%87%D8%A7%DB%8C_%D8%AF%D9%86%D8%A7_%DA%A9%D9%87%DA%AF%DB%8C%D9%84%D9%88%DB%8C%D8%B1_%D8%A7%D8%AD%D9%85%D8%AF_-_panoramio.jpg" target="_blank" rel="noopener noreferrer" class="hover:text-white">
                عکس کوهستان دنا: Farid Atar · CC BY 3.0
            </a>
        </div>
    </footer>

    <a href="#top" x-show="hasScrolled" x-cloak x-transition.opacity.duration.200ms
       class="fixed bottom-5 left-5 z-30 flex h-12 w-12 items-center justify-center rounded-full bg-[#19392f] text-white shadow-xl ring-1 ring-white/20 md:bottom-8 md:left-8"
       aria-label="بازگشت به بالای صفحه">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 19V5m-6 6 6-6 6 6"/></svg>
    </a>

    {{-- Only the product catalog is dynamic; safely-escaped, page-limited public fields. --}}
    <script id="landing-product-data" type="application/json">@json($productPreviews, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>

    {{-- Native dialog provides a top-layer backdrop, focus containment, Escape and mobile support. --}}
    <dialog id="denapax-product-preview" x-ref="productDialog"
        x-on:close="onProductClosed()" x-on:click.self="closeProduct()"
        aria-labelledby="landing-preview-title"
        class="landing-product-dialog">
        <template x-if="preview">
            <div class="landing-product-sheet">
                <div class="landing-product-visual">
                    <div class="landing-product-visual-main">
                        <template x-if="preview.images.length">
                            <img x-bind:src="preview.images[previewImageIndex].url"
                                 x-bind:alt="preview.images[previewImageIndex].alt"
                                 class="h-full w-full object-cover" width="720" height="720" decoding="async">
                        </template>
                        <template x-if="!preview.images.length">
                            <div class="organic-lines flex h-full w-full items-center justify-center bg-[#cad0bb]">
                                <svg viewBox="0 0 110 110" class="h-27 w-27 text-[#547560]" fill="none" aria-hidden="true">
                                    <path d="m7 83 36-58 22 34 14-23 25 47M14 90h84" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                        </template>
                        <span class="absolute right-5 top-5 rounded-full bg-[#f0eee2]/90 px-4 py-2 text-xs font-semibold text-[#355643]" x-text="preview.category"></span>
                        <template x-if="preview.images.length > 1">
                            <div class="landing-product-photo-nav">
                                <button type="button" x-on:click="nextImage(-1)" aria-label="تصویر قبلی">→</button>
                                <span dir="ltr" class="text-xs font-medium" x-text="(previewImageIndex + 1) + ' / ' + preview.images.length"></span>
                                <button type="button" x-on:click="nextImage(1)" aria-label="تصویر بعدی">←</button>
                            </div>
                        </template>
                    </div>
                    <template x-if="preview.images.length > 1">
                        <div class="landing-product-thumbnails" aria-label="انتخاب تصویر محصول">
                            <template x-for="(image, index) in preview.images" x-bind:key="image.url">
                                <button type="button" class="landing-product-thumbnail"
                                    x-bind:class="{ 'is-selected': index === previewImageIndex }"
                                    x-bind:aria-label="'تصویر ' + (index + 1) + ' محصول'"
                                    x-bind:aria-pressed="(index === previewImageIndex).toString()"
                                    x-on:click="selectImage(index)">
                                    <img x-bind:src="image.url" x-bind:alt="image.alt"
                                         width="72" height="72" loading="lazy" decoding="async">
                                </button>
                            </template>
                        </div>
                    </template>
                </div>

                <div class="landing-product-info">
                    <div class="flex items-start justify-between gap-4">
                        <span class="landing-label text-[#9b7958]" lang="en">THE DENA COLLECTION</span>
                        <button data-product-close type="button" x-on:click="closeProduct()"
                            class="landing-product-close" aria-label="بستن جزئیات محصول">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 5 14 14M19 5 5 19"/></svg>
                        </button>
                    </div>

                    <div class="mt-6">
                        <h2 id="landing-preview-title" class="text-[clamp(1.75rem,3.8vw,2.8rem)] font-extrabold leading-[1.5] tracking-tight text-[#1d4130]" x-text="preview.title"></h2>
                        <p class="mt-2 text-sm leading-8 text-[#6a7b6d]" x-show="preview.summary" x-text="preview.summary"></p>
                        <p class="mt-4 whitespace-pre-line text-sm leading-8 text-[#3f5c49]" x-show="preview.description" x-text="preview.description"></p>
                    </div>

                    <div class="my-7 h-px bg-[#d5dbcc]"></div>

                    <section aria-labelledby="landing-variants-title">
                        <h3 id="landing-variants-title" class="mb-4 text-base font-bold text-[#234b39]">تنوع‌های قابل سفارش</h3>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <template x-for="(variant, index) in preview.variants" x-bind:key="index">
                                <button type="button" class="landing-variant-button"
                                    x-bind:class="{ 'is-selected': previewVariantIndex === index }"
                                    x-bind:aria-pressed="(previewVariantIndex === index).toString()"
                                    x-on:click="selectVariant(index)">
                                    <span class="flex items-center justify-between gap-2 text-right">
                                        <span class="text-sm font-bold" x-text="variant.title"></span>
                                        <span class="landing-variant-radio" aria-hidden="true"></span>
                                    </span>
                                    <span class="mt-2 block text-xs opacity-70" x-text="variant.size"></span>
                                    <span class="mt-3 block text-sm font-bold">
                                        <span dir="ltr" class="inline-block" x-text="variant.price"></span>
                                        <span class="text-xs font-normal">تومان</span>
                                    </span>
                                </button>
                            </template>
                        </div>
                    </section>

                    <template x-if="preview.features.length">
                        <section class="mt-8" aria-labelledby="landing-features-title">
                            <h3 id="landing-features-title" class="mb-3 text-sm font-bold text-[#234b39]">ویژگی‌های محصول</h3>
                            <dl class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <template x-for="(feature, index) in preview.features" x-bind:key="index">
                                    <div class="flex items-baseline justify-between gap-4 border-b border-[#d4dccd] py-2 text-xs">
                                        <dt class="text-[#738474]" x-text="feature.name"></dt>
                                        <dd class="text-left font-semibold text-[#294d3b]" x-text="feature.value"></dd>
                                    </div>
                                </template>
                            </dl>
                        </section>
                    </template>

                    <div class="mt-8 rounded-2xl bg-[#e8ebdf] p-4">
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="text-sm text-[#637867]">قیمت تنوع انتخاب‌شده</span>
                            <span class="text-xl font-extrabold text-[#1d4130]">
                                <span dir="ltr" class="inline-block" x-text="preview.variants[previewVariantIndex].price"></span>
                                <span class="text-xs font-medium">تومان</span>
                            </span>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('cart.add') }}" class="mt-5 space-y-3" x-on:submit.prevent="submitPreviewCart()">
                        @csrf
                        <input type="hidden" name="variant_id" x-bind:value="preview.variants[previewVariantIndex].id">
                        <label for="landing-cart-quantity" class="flex items-center justify-between gap-3 text-sm font-bold text-[#315642]">
                            تعداد
                            <input id="landing-cart-quantity" type="number" name="quantity" min="1" max="1000" step="1" required
                                   class="landing-cart-quantity" x-model.number="previewQuantity">
                        </label>
                        <button type="submit"
                                class="landing-button w-full bg-[#19392f] text-center text-white hover:bg-[#2b5642]"
                                x-bind:disabled="cartSubmitting"
                                x-text="cartSubmitting ? 'در حال افزودن...' : 'افزودن به سبد خرید'">افزودن به سبد خرید</button>
                    </form>
                    <p x-show="cartError" x-cloak role="alert" class="mt-3 rounded-xl bg-red-50 px-4 py-2 text-sm text-red-700" x-text="cartError"></p>
                    <a x-bind:href="preview.url" class="mt-3 block min-h-11 py-3 text-center text-xs font-bold text-[#42624e] underline underline-offset-4">مشاهده صفحه کامل محصول</a>
                </div>
            </div>
        </template>
    </dialog>
    <livewire:store.add-to-cart />
    @livewireScripts
</body>
</html>
