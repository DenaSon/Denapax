<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#19392f">
    <meta name="description" content="دناپکس؛ آشنایی با سوغات، خشکبار و محصولات محلی دنا. محصولات را ببینید و سفارش خود را در بات تلگرام ثبت کنید.">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="fa_IR">
    <meta property="og:site_name" content="DenaPax">
    <meta property="og:title" content="دناپکس | از دل دنا">
    <meta property="og:description" content="روایتی از طعم‌های محلی دنا؛ معرفی محصولات و سفارش از طریق تلگرام.">
    <meta property="og:url" content="{{ route('home') }}">
    <link rel="canonical" href="{{ route('home') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="preload" href="{{ asset('images/dena-landscape.svg') }}" as="image" type="image/svg+xml">
    <title>دناپکس | از دل دنا</title>
    @vite(['resources/css/landing.css', 'resources/js/landing.js'])
</head>
<body class="landing landing-shell antialiased" x-data="landingPage()" x-on:keydown.escape.window="menuOpen && closeMenu(true)">
    <div class="landing-progress" x-cloak x-bind:style="'--landing-progress: ' + scrollProgress" aria-hidden="true"></div>
    <nav class="landing-chapter-rail" aria-label="بخش‌های صفحه" x-cloak>
        <a href="#top" x-bind:aria-current="activeSection === 'top' ? 'location' : null" aria-label="ابتدای صفحه">01</a>
        <a href="#products" x-bind:aria-current="activeSection === 'products' ? 'location' : null" aria-label="محصولات">02</a>
        <a href="#story" x-bind:aria-current="activeSection === 'story' ? 'location' : null" aria-label="روایت دنا">03</a>
        <a href="#order" x-bind:aria-current="activeSection === 'order' ? 'location' : null" aria-label="سفارش">04</a>
    </nav>
    <a class="sr-only focus:not-sr-only focus:fixed focus:z-50 focus:top-3 focus:right-3 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2" href="#products">رفتن به محصولات</a>

    <header class="absolute inset-x-0 top-0 z-30 text-white">
        <div class="landing-container flex h-22 items-center justify-between gap-5 border-b border-white/20 md:h-26">
            <a href="{{ route('home') }}" aria-label="دناپکس، بازگشت به ابتدای صفحه" class="group inline-flex shrink-0 items-center gap-3" x-on:click="menuOpen = false">
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
                <span class="text-xs tracking-[.25em] text-white/60" lang="en">NATURE • ORIGIN • TASTE</span>
                @if ($telegramUrl)
                    <a class="landing-button border border-white/60 text-sm hover:bg-white hover:text-forest" href="{{ $telegramUrl }}" target="_blank" rel="noopener noreferrer">
                        ورود به بات
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m19 5-14 14M19 5H9m10 0v10"/></svg>
                    </a>
                @else
                    <a class="landing-button border border-white/60 text-sm hover:bg-white hover:text-forest" href="#products">کشف محصولات</a>
                @endif
            </div>

            <button type="button" x-ref="menuToggle" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/40 text-white lg:hidden" x-on:click="toggleMenu()" x-bind:aria-expanded="menuOpen.toString()" x-bind:aria-label="menuOpen ? 'بستن منو' : 'باز کردن منو'" aria-controls="landing-mobile-nav">
                <svg x-show="!menuOpen" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                <svg x-show="menuOpen" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m5 5 14 14M19 5 5 19"/></svg>
            </button>
        </div>
        <nav id="landing-mobile-nav" x-ref="mobileNav" x-show="menuOpen" x-cloak x-transition.opacity.duration.200ms x-on:keydown="onMobileNavKeydown($event)" x-on:click.outside="closeMenu()" aria-label="فهرست موبایل" class="landing-container mt-3 rounded-2xl border border-white/10 bg-[#18352d] p-5 shadow-2xl lg:hidden">
            <a x-on:click="closeMenu(true)" href="#products" class="block border-b border-white/15 py-3 text-sm">محصولات</a>
            <a x-on:click="closeMenu(true)" href="#story" class="block border-b border-white/15 py-3 text-sm">روایت دنا</a>
            <a x-on:click="closeMenu(true)" href="#order" class="block py-3 text-sm">سفارش در تلگرام</a>
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
            <img x-ref="landscape" class="landing-landscape" src="{{ asset('images/dena-landscape.svg') }}" width="1600" height="1100" alt="" aria-hidden="true" fetchpriority="high">
            <div class="landing-hero-glow" aria-hidden="true"></div>
            <div class="landing-container relative flex min-h-[690px] flex-col justify-center pb-28 pt-38 md:min-h-[820px] md:pb-32 md:pt-40">
                <div class="absolute left-0 top-36 hidden flex-col items-end gap-3 lg:flex" aria-hidden="true">
                    <span class="text-xs tracking-[.32em] text-white/65" lang="en">THE DENA COLLECTION</span>
                    <span class="h-32 w-px bg-white/40"></span>
                    <span class="text-xs text-white/60">۰۱ / ۰۴</span>
                </div>
                <div class="max-w-[690px]" data-reveal>
                    <p class="landing-label mb-7 flex items-center gap-3 text-[#e3d4b7]">
                        <span class="h-px w-8 bg-[#ddc6a1]"></span>
                        طعم‌هایی با ریشه در طبیعت
                    </p>
                    <h1 id="hero-title" class="landing-display text-[clamp(4rem,9vw,8.5rem)]">
                        از دل
                        <span class="block landing-outline">دنا</span>
                    </h1>
                    <p class="mt-7 max-w-[440px] text-[15px] leading-9 text-white/85 md:mt-9 md:text-lg md:leading-10">
                        مجموعه‌ای از سوغات و خشکبار؛ با طعم‌ها و روایت‌هایی از سرزمین دنا.
                    </p>
                    <div class="mt-9 flex flex-wrap items-center gap-5 md:mt-12">
                        <a href="#products" class="landing-button bg-[#e7e3d1] text-[#19392f] shadow-xl shadow-black/10 hover:bg-white">
                            محصولات را ببینید
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m5 5 14 14M5 19h14V5"/></svg>
                        </a>
                        <span class="text-xs text-white/65">سوغات و خشکبار دناپکس</span>
                    </div>
                </div>
                <div class="landing-orbit" aria-hidden="true">
                    <span lang="en">DENA<br>ROOTS · TASTE<br>31° N</span>
                </div>
                <div class="absolute bottom-9 right-0 left-0 flex items-end justify-between text-[11px] text-white/70 md:bottom-12">
                    <div class="flex items-center gap-4">
                        <span class="inline-block h-7 w-px bg-white/60"></span>
                        <span>برای دیدن محصولات اسکرول کنید</span>
                    </div>
                    <span class="tracking-[.25em]" lang="en">DENAPAX — 01</span>
                </div>
            </div>
        </section>

        <section class="landing-container py-20 md:py-30" aria-label="درباره مجموعه">
            <div class="grid items-start gap-10 lg:grid-cols-[1fr_2fr]" data-reveal>
                <div>
                    <p class="landing-label text-clay">A sense of place</p>
                    <p class="mt-2 text-sm text-[#61776a]">برآمده از یک سرزمین</p>
                </div>
                <p class="max-w-[900px] text-[clamp(1.75rem,3.8vw,3.75rem)] font-bold leading-[1.9] tracking-tight">
                    هر محصول، <span class="text-[#8c9a81]">روایتی از یک جاست.</span>
                    دناپکس این طعم‌ها را کنار هم می‌آورد تا انتخابشان ساده‌تر باشد.
                </p>
            </div>
        </section>

        <section id="products" data-chapter class="landing-container scroll-mt-12 pb-25 md:pb-36" aria-labelledby="products-title">
            <div class="landing-divider mb-12 md:mb-16"></div>
            <div class="mb-10 flex flex-col justify-between gap-6 md:mb-14 md:flex-row md:items-end">
                <div data-reveal>
                    <div class="mb-4 flex items-center gap-3 text-xs font-semibold tracking-widest text-clay">
                        <span class="h-px w-8 bg-clay"></span>
                        THE COLLECTION / 02
                    </div>
                    <h2 id="products-title" class="landing-display text-[clamp(2.9rem,6.2vw,6.1rem)]">
                        انتخاب‌های <span class="landing-outline-dark">دنا</span>
                    </h2>
                    <p class="mt-5 text-sm leading-8 text-[#718276]">محصولات منتشرشده فروشگاه، مستقیم از کاتالوگ دناپکس</p>
                </div>
                <span class="text-xs text-[#718276]" data-reveal>
                    {{ $products->total() }} محصول
                </span>
            </div>

            @if ($categories->isNotEmpty())
                <nav aria-label="فیلتر دسته‌بندی محصولات" class="mb-8 flex flex-wrap items-center gap-2.5 md:mb-11">
                    <a href="{{ route('home') }}#products" aria-current="{{ $selectedCategory === null ? 'page' : 'false' }}"
                        class="{{ $selectedCategory === null ? 'bg-[#19392f] text-white' : 'border border-[#c9cec0] bg-white/45 text-[#3e604d] hover:border-[#3e604d]' }} rounded-full px-5 py-2.5 text-xs font-semibold transition-colors">
                        همه محصولات
                    </a>
                    @foreach ($categories as $category)
                        <a href="{{ route('home', ['category' => $category->slug]) }}#products" aria-current="{{ $selectedCategory === $category->slug ? 'page' : 'false' }}"
                            class="{{ $selectedCategory === $category->slug ? 'bg-[#19392f] text-white' : 'border border-[#c9cec0] bg-white/45 text-[#3e604d] hover:border-[#3e604d]' }} rounded-full px-5 py-2.5 text-xs font-semibold transition-colors">
                            {{ $category->name }}
                        </a>
                    @endforeach
                </nav>
            @endif

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 lg:gap-7">
                @forelse ($products as $product)
                    @php
                        $isFeatured = $loop->first && $products->currentPage() === 1;
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
                    <article class="product-card group relative overflow-hidden bg-[#ebe9dd] {{ $isFeatured ? 'sm:col-span-2 lg:col-span-2 product-card-featured' : '' }}" data-reveal data-delay="{{ $loop->index % 3 }}">
                        <a href="{{ $telegramUrl ?? '#order' }}" @if ($telegramUrl) target="_blank" rel="noopener noreferrer" @endif
                           x-on:click.prevent="openProduct({{ $product->id }}, $event)"
                           aria-haspopup="dialog" aria-controls="denapax-product-preview"
                           class="block h-full" aria-label="مشاهده جزئیات {{ $product->title }}">
                            <div class="product-photo relative overflow-hidden bg-[#c8cbb6] {{ $isFeatured ? 'max-h-[570px]' : '' }}">
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
                                @if ($isFeatured)
                                    <span class="absolute bottom-5 right-5 text-xs tracking-[.22em] text-white drop-shadow-sm" lang="en">DENAPAX / SELECTED</span>
                                @endif
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
                                <span class="product-arrow flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-[#91a08d] text-[#234635]" aria-hidden="true">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                                </span>
                            </div>
                        </a>
                    </article>
                @empty
                    <div class="col-span-full organic-lines rounded-3xl border border-[#d6d9cc] bg-[#e9eadb] p-12 text-center md:p-24">
                        <p class="mb-4 text-sm font-semibold text-[#71816c]">COLLECTION / 00</p>
                        <h3 class="text-2xl font-bold text-[#254736] md:text-3xl">محصولی برای نمایش پیدا نشد</h3>
                        <p class="mt-4 text-sm leading-8 text-[#637366]">محصولات منتشرشده و قابل سفارش به‌صورت خودکار در این بخش نمایش داده می‌شوند.</p>
                        @if ($selectedCategory)
                            <a href="{{ route('home') }}#products" class="mt-6 inline-flex text-sm font-bold underline underline-offset-8">نمایش همه محصولات</a>
                        @endif
                    </div>
                @endforelse
            </div>

            @if ($products->hasPages())
                <nav class="mt-12 flex items-center justify-between border-t border-[#c9cec0] pt-7 text-sm font-semibold" aria-label="صفحات محصولات">
                    @if ($products->previousPageUrl())
                        <a class="transition hover:text-clay" href="{{ $products->previousPageUrl() }}#products">صفحه قبل ←</a>
                    @else
                        <span class="text-[#9da99c]">صفحه قبل</span>
                    @endif
                    <span class="text-xs text-[#718276]">صفحه {{ $products->currentPage() }} از {{ $products->lastPage() }}</span>
                    @if ($products->nextPageUrl())
                        <a class="transition hover:text-clay" href="{{ $products->nextPageUrl() }}#products">→ صفحه بعد</a>
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
                    <p class="landing-label mb-6 text-[#8b6e54]">FROM THE LAND / 03</p>
                    <h2 id="story-title" class="landing-display text-[clamp(3rem,5.9vw,6.7rem)]">
                        یک نام،
                        <span class="block landing-outline-dark">یک اقلیم</span>
                    </h2>
                    <p class="mt-8 max-w-[550px] text-base leading-10 text-[#536956]">
                        دنا فقط یک نام روی نقشه نیست؛ کوهستان و شهرها و روستاهایی است که شیوه زندگی و طعم‌های محلی خود را دارند. دناپکس جایی برای آشنا شدن با بخشی از این محصولات است.
                    </p>
                    <div class="mt-9 flex items-center gap-4 text-xs font-bold tracking-wider text-[#536956]">
                        <span class="h-px w-10 bg-[#536956]"></span>
                        الهام‌گرفته از طبیعت دنا
                    </div>
                </div>
                <div class="relative mx-auto flex aspect-square w-full max-w-[550px] items-center justify-center rounded-full border border-[#738e78]/35 p-7 md:p-12" data-reveal data-delay="1">
                    <div class="absolute inset-8 rounded-full border border-[#738e78]/35 md:inset-15"></div>
                    <div class="absolute inset-17 rounded-full border border-[#738e78]/25 md:inset-26"></div>
                    <div class="relative flex h-[75%] w-[75%] flex-col items-center justify-center rounded-full bg-[#214536] text-[#ecedda] shadow-[0_35px_80px_-35px_rgba(25,57,47,.5)]">
                        <svg class="h-20 w-20 md:h-26 md:w-26" viewBox="0 0 110 100" fill="none" aria-hidden="true">
                            <path d="m5 76 36-54 20 31 15-22 30 45" stroke="#e1e5cc" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="m31 37 10-15 14 22-15-8-10 12m42-11 5-6 12 17-12-6-7 8" fill="#e1e5cc"/>
                            <path d="M7 84h96" stroke="#e1e5cc" stroke-width="2"/>
                        </svg>
                        <span class="mt-6 text-xl font-bold md:text-3xl">دناپکس</span>
                        <span class="mt-2 text-[9px] tracking-[.3em] text-[#d5dbc2] md:text-xs" lang="en">ROOTED IN DENA</span>
                    </div>
                    <span class="absolute right-0 top-1/2 -translate-y-1/2 rounded-full bg-[#e2e5d5] px-2 text-xs text-[#56705b]" lang="en">31 / NATURE</span>
                </div>
            </div>
        </section>

        <section id="order" data-chapter class="relative overflow-hidden bg-[#17382d] py-25 text-[#f3f1e5] md:py-36" aria-labelledby="order-title">
            <img class="pointer-events-none absolute -left-[15%] top-[-65%] h-[170%] w-[80%] object-cover opacity-[.16]" src="{{ asset('images/dena-contours.svg') }}" width="700" height="600" alt="" aria-hidden="true" loading="lazy">
            <div class="landing-container relative" data-reveal>
                <p class="landing-label mb-9 text-[#d3bf9b]">A SIMPLE WAY TO ORDER / 04</p>
                <h2 id="order-title" class="landing-display max-w-[850px] text-[clamp(2.9rem,6vw,6.8rem)]">
                    از اینجا
                    <span class="block landing-outline">به تلگرام.</span>
                </h2>
                <p class="mt-7 max-w-[550px] text-base leading-9 text-[#e0e6d7]/85">
                    محصولات را در اینجا ببینید؛ برای انتخاب تنوع، ثبت سفارش و پیگیری، از بات دناپکس استفاده کنید.
                </p>
                @if ($telegramUrl)
                    <a href="{{ $telegramUrl }}" target="_blank" rel="noopener noreferrer" class="landing-button mt-10 bg-[#e8e8d7] text-[#183b2e] hover:bg-white">
                        ورود به فروشگاه تلگرام
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m4 12 16-8-4 16-4-6-4-2zM12 14l8-10"/></svg>
                    </a>
                @else
                    <p class="mt-8 inline-flex rounded-full border border-white/30 px-6 py-4 text-sm text-white/80" role="status">لینک بات در حال آماده‌سازی است</p>
                @endif
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
            <span lang="en" dir="ltr">DESIGNED WITH DENA IN MIND</span>
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

                    @if ($telegramUrl)
                        <a href="{{ $telegramUrl }}" target="_blank" rel="noopener noreferrer"
                           class="landing-button mt-4 w-full bg-[#19392f] text-center text-white hover:bg-[#2b5642]">
                            ادامه سفارش در بات تلگرام
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m4 12 16-8-4 16-4-6-4-2zM12 14l8-10"/></svg>
                        </a>
                    @else
                        <a href="#order" x-on:click="closeProduct()"
                           class="landing-button mt-4 w-full bg-[#19392f] text-white">
                            راهنمای سفارش
                        </a>
                    @endif
                    <p class="mt-3 text-center text-xs leading-6 text-[#7b8a7a]">
                        انتخاب نهایی تنوع و ثبت سفارش داخل بات انجام می‌شود.
                    </p>
                </div>
            </div>
        </template>
    </dialog>
</body>
</html>
