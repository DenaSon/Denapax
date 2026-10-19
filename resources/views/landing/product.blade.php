<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#19392f">
    <meta name="robots" content="index,follow,max-image-preview:large">
    <meta name="description" content="{{ $metaDescription }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta property="og:type" content="product">
    <meta property="og:locale" content="fa_IR">
    <meta property="og:site_name" content="DenaPax">
    <meta property="og:title" content="{{ $product->title }} | دناپکس">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    @if ($photos->isNotEmpty())
        <meta property="og:image" content="{{ $photos->first()['full_url'] }}">
        <meta property="og:image:alt" content="تصویر {{ $product->title }}">
        <meta name="twitter:card" content="summary_large_image">
    @else
        <meta name="twitter:card" content="summary">
    @endif
    <title>{{ $product->title }} | سوغات و خشکبار دناپکس</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @if ($photos->isNotEmpty())
        <link rel="preload" as="image" href="{{ $photos->first()['url'] }}">
    @endif
    @vite(['resources/css/landing.css'])
    <script type="application/ld+json">@json($schema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE)</script>
</head>
<body class="landing landing-shell antialiased">
    <a href="#product-content" class="sr-only focus:not-sr-only focus:fixed focus:z-50 focus:top-3 focus:right-3 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2">رفتن به جزئیات محصول</a>

    <header class="relative bg-[#17382d] text-[#f4f0e7]">
        <div class="landing-container flex min-h-22 flex-wrap items-center justify-between gap-4 py-4 md:min-h-26">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-3 rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#e8d3b1]" aria-label="بازگشت به صفحه اصلی دناپکس">
                <svg class="h-9 w-11" viewBox="0 0 70 48" fill="none" aria-hidden="true">
                    <path d="M3 40 26 8l15 22 9-13 17 23" stroke="#E6E8D4" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="m19 18 7-10 10 15-8-5-6 8" fill="#E6E8D4"/>
                    <path d="M3 46h64" stroke="#E6E8D4" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <span class="text-xl font-extrabold tracking-tight" lang="en">DenaPax</span>
            </a>
            <nav aria-label="ناوبری محصول" class="flex flex-wrap items-center gap-5 text-xs font-semibold text-[#e6e8d4] sm:text-sm">
                <a class="transition hover:text-white" href="{{ route('home') }}#products">همه محصولات</a>
                <a class="transition hover:text-white" href="{{ route('home') }}#story">روایت دنا</a>
                @if ($telegramUrl)
                    <a class="rounded-full border border-white/40 px-4 py-2 transition hover:bg-white hover:text-[#19392f]" href="{{ $telegramUrl }}" target="_blank" rel="noopener noreferrer">بات سفارش</a>
                @endif
            </nav>
        </div>
    </header>

    <main id="product-content" class="landing-container pb-24 pt-8 md:pb-32 md:pt-12">
        <nav aria-label="مسیر صفحه" class="mb-10 flex flex-wrap items-center gap-2 text-xs text-[#627664]">
            <a class="hover:text-[#1d4130]" href="{{ route('home') }}">خانه</a>
            <span aria-hidden="true">/</span>
            <a class="hover:text-[#1d4130]" href="{{ route('home') }}?category={{ urlencode($product->category->slug) }}#products">{{ $product->category->name }}</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page" class="font-semibold text-[#244633]">{{ $product->title }}</span>
        </nav>

        <div class="grid items-start gap-9 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,.9fr)] lg:gap-15">
            <div class="min-w-0">
                <div class="relative aspect-square overflow-hidden rounded-[1.65rem] bg-[#cdd4bf] md:aspect-[1.1/1]">
                    @if ($photos->isNotEmpty())
                        <img class="h-full w-full object-cover" src="{{ $photos->first()['url'] }}"
                             alt="تصویر {{ $product->title }}" width="960" height="960" fetchpriority="high" decoding="async">
                    @else
                        <div class="organic-lines flex h-full w-full items-center justify-center">
                            <svg class="h-32 w-32 text-[#688872]" viewBox="0 0 120 120" fill="none" aria-hidden="true">
                                <path d="m5 89 38-61 26 37 16-24 30 48M14 97h92" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    @endif
                    <span class="absolute right-5 top-5 rounded-full bg-[#f3f1e8]/90 px-5 py-2 text-xs font-bold text-[#315844]">{{ $product->category->name }}</span>
                </div>
                @if ($photos->count() > 1)
                    <div class="mt-4 grid grid-cols-3 gap-3 sm:grid-cols-4" aria-label="تصاویر دیگر محصول">
                        @foreach ($photos->skip(1) as $photo)
                            <a href="{{ $photo['full_url'] }}" target="_blank" rel="noopener noreferrer" class="block aspect-square overflow-hidden rounded-xl bg-[#d5dac9]" aria-label="نمایش تصویر {{ $loop->iteration + 1 }} محصول در اندازه اصلی">
                                <img src="{{ $photo['url'] }}" alt="نمای دیگر {{ $product->title }}"
                                     width="260" height="260" loading="lazy" decoding="async" class="h-full w-full object-cover transition-transform duration-500 hover:scale-105">
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <article class="min-w-0 lg:pt-6">
                <div class="landing-label mb-5 text-[#a57c56]" lang="en">THE DENA COLLECTION / PRODUCT</div>
                <h1 class="landing-display text-[clamp(2.35rem,4.6vw,4.9rem)] text-[#1b4231]">{{ $product->title }}</h1>

                @if ($product->summary)
                    <p class="mt-6 text-base leading-9 text-[#506b58]">{{ $product->summary }}</p>
                @endif
                @if ($product->description)
                    <p class="mt-5 whitespace-pre-line text-sm leading-9 text-[#5d725e]">{{ $product->description }}</p>
                @endif

                <div class="my-9 h-px bg-[#cdd5c6]"></div>
                <div class="rounded-2xl bg-[#e6ebdd] p-5 md:p-7">
                    <p class="text-xs font-medium text-[#748673]">قیمت از</p>
                    <p class="mt-2 text-3xl font-extrabold text-[#1d4433]">
                        <span dir="ltr" class="inline-block">{{ \App\Support\Money::formatToman($minimumPrice) }}</span>
                        <span class="text-sm font-medium">تومان</span>
                    </p>
                    <p class="mt-3 text-xs leading-6 text-[#627565]">قیمت نهایی به تنوع انتخاب‌شده بستگی دارد.</p>
                </div>

                <section class="mt-9" aria-labelledby="product-variants">
                    <h2 id="product-variants" class="text-xl font-extrabold text-[#264c38]">تنوع‌های قابل سفارش</h2>
                    <ul class="mt-5 grid gap-3 sm:grid-cols-2">
                        @foreach ($product->variants as $variant)
                            @php
                                $quantity = rtrim(rtrim((string) $variant->sale_quantity, '0'), '.');
                                $unit = match ($variant->sale_unit) {
                                    'g' => 'گرم', 'kg' => 'کیلوگرم', 'l' => 'لیتر',
                                    'piece' => 'عدد', 'pack' => 'بسته',
                                    default => $variant->sale_unit,
                                };
                            @endphp
                            <li class="rounded-2xl border border-[#cbd4c7] bg-[#f8f7ef] p-5">
                                <h3 class="text-sm font-bold text-[#234835]">{{ $variant->title }}</h3>
                                <p class="mt-2 text-xs text-[#708470]">{{ $quantity }} {{ $unit }}</p>
                                <p class="mt-4 text-base font-extrabold text-[#1c4432]">
                                    <span dir="ltr" class="inline-block">{{ \App\Support\Money::formatToman((int) $variant->price_irr) }}</span>
                                    <span class="text-xs font-normal">تومان</span>
                                </p>
                            </li>
                        @endforeach
                    </ul>
                </section>

                @if ($product->features->isNotEmpty())
                    <section class="mt-9" aria-labelledby="product-features">
                        <h2 id="product-features" class="text-lg font-extrabold text-[#264c38]">ویژگی‌های محصول</h2>
                        <dl class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2">
                            @foreach ($product->features as $feature)
                                <div class="flex items-start justify-between gap-3 border-b border-[#cdd5c6] py-3 text-sm">
                                    <dt class="text-[#748575]">{{ $feature->name }}</dt>
                                    <dd class="text-left font-semibold text-[#31513d]">{{ $feature->value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>
                @endif

                <div class="mt-10">
                    @if ($telegramUrl)
                        <a href="{{ $telegramUrl }}" target="_blank" rel="noopener noreferrer"
                           class="landing-button w-full bg-[#19392f] text-white hover:bg-[#315844] sm:w-auto">
                            انتخاب تنوع و سفارش در تلگرام
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m4 12 16-8-4 16-4-6-4-2zM12 14l8-10"/></svg>
                        </a>
                    @else
                        <span role="status" class="inline-block rounded-full border border-[#91a392] px-5 py-3 text-sm text-[#58705c]">لینک بات در حال آماده‌سازی است</span>
                    @endif
                    <p class="mt-4 text-xs leading-6 text-[#728272]">ثبت سفارش و انتخاب نهایی تنوع در بات انجام می‌شود.</p>
                </div>
            </article>
        </div>

        <div class="mt-15 border-t border-[#cbd5c4] pt-8">
            <a class="inline-flex items-center gap-3 text-sm font-bold text-[#315743] hover:text-[#1b372b]" href="{{ route('home') }}#products">
                <span aria-hidden="true">→</span>
                بازگشت به همه محصولات
            </a>
        </div>
    </main>

    <footer class="bg-[#122e26] py-9 text-[#d6dfcf]">
        <div class="landing-container flex flex-wrap items-center justify-between gap-5">
            <a href="{{ route('home') }}" class="text-xl font-extrabold text-white" lang="en">DenaPax.</a>
            <span class="text-xs">سوغات و خشکبار؛ از دل دنا</span>
        </div>
    </footer>
</body>
</html>
