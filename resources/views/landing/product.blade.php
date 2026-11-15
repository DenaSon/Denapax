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
    @vite(['resources/css/landing.css', 'resources/js/product-gallery.js'])
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
                <a class="rounded-full border border-white/40 px-4 py-2 transition hover:bg-white hover:text-[#19392f]" href="{{ route('cart.index') }}">سبد خرید</a>
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
            <div class="min-w-0" data-product-gallery aria-label="گالری تصاویر {{ $product->title }}">
                @if ($photos->isNotEmpty())
                    <div class="product-gallery-stage">
                        <a href="{{ $photos->first()['full_url'] }}" target="_blank" rel="noopener noreferrer"
                           data-gallery-open class="product-gallery-main-link"
                           aria-label="نمایش بزرگ تصویر {{ $product->title }}">
                            <img data-gallery-main class="product-gallery-main-image"
                                 src="{{ $photos->first()['url'] }}" alt="تصویر ۱ از {{ $product->title }}"
                                 width="960" height="960" fetchpriority="high" decoding="async">
                            <span class="product-gallery-zoom-hint" aria-hidden="true">⌕ <span>بزرگ‌نمایی</span></span>
                        </a>
                        <span class="product-gallery-category">{{ $product->category->name }}</span>
                        @if ($photos->count() > 1)
                            <button type="button" class="product-gallery-arrow product-gallery-prev"
                                    data-gallery-step="-1" aria-label="تصویر قبلی"><span aria-hidden="true">›</span></button>
                            <button type="button" class="product-gallery-arrow product-gallery-next"
                                    data-gallery-step="1" aria-label="تصویر بعدی"><span aria-hidden="true">‹</span></button>
                            <span class="product-gallery-counter" data-gallery-counter aria-live="polite">۱ / {{ $photos->count() }}</span>
                        @endif
                    </div>
                    @if ($photos->count() > 1)
                        <div class="product-gallery-thumbs" aria-label="انتخاب تصویر محصول" data-gallery-thumbs>
                            @foreach ($photos as $photo)
                                <a href="{{ $photo['full_url'] }}" target="_blank" rel="noopener noreferrer"
                                   class="product-gallery-thumb"
                                   data-gallery-thumb data-gallery-index="{{ $loop->index }}"
                                   data-image-url="{{ $photo['url'] }}"
                                   data-image-full-url="{{ $photo['full_url'] }}"
                                   data-image-alt="تصویر {{ $loop->iteration }} از {{ $product->title }}"
                                   aria-label="نمایش تصویر {{ $loop->iteration }} از {{ $photos->count() }}"
                                   @if ($loop->first) aria-current="true" @endif>
                                    <img src="{{ $photo['url'] }}" alt="" width="120" height="120" loading="lazy" decoding="async">
                                </a>
                            @endforeach
                        </div>
                    @else
                        <a class="product-gallery-single-fallback" href="{{ $photos->first()['full_url'] }}"
                           target="_blank" rel="noopener noreferrer">مشاهده تصویر در اندازه اصلی</a>
                        <a href="{{ $photos->first()['full_url'] }}" target="_blank" rel="noopener noreferrer"
                           data-gallery-thumb data-gallery-index="0"
                           data-image-url="{{ $photos->first()['url'] }}"
                           data-image-full-url="{{ $photos->first()['full_url'] }}"
                           data-image-alt="تصویر ۱ از {{ $product->title }}" hidden></a>
                    @endif

                    <dialog class="product-lightbox" data-gallery-lightbox aria-label="نمایش تمام‌صفحه تصاویر {{ $product->title }}">
                        <div class="product-lightbox-header">
                            <span class="product-lightbox-title">{{ $product->title }}</span>
                            <button type="button" class="product-lightbox-control product-lightbox-close"
                                    data-lightbox-close aria-label="بستن نمایش تمام‌صفحه">✕</button>
                        </div>
                        <div class="product-lightbox-view" data-lightbox-viewport>
                            <img data-lightbox-image class="product-lightbox-image"
                                 alt="تصویر ۱ از {{ $product->title }}" width="1280" height="1280"
                                 decoding="async">
                        </div>
                        <div class="product-lightbox-toolbar">
                            <div class="product-lightbox-group">
                                @if ($photos->count() > 1)
                                    <button type="button" class="product-lightbox-control" data-lightbox-step="-1"
                                            aria-label="تصویر قبلی">›</button>
                                    <span class="product-lightbox-count" data-lightbox-counter aria-live="polite">۱ / {{ $photos->count() }}</span>
                                    <button type="button" class="product-lightbox-control" data-lightbox-step="1"
                                            aria-label="تصویر بعدی">‹</button>
                                @endif
                            </div>
                            <div class="product-lightbox-group">
                                <button type="button" class="product-lightbox-control" data-lightbox-zoom-out aria-label="کوچک‌نمایی">−</button>
                                <button type="button" class="product-lightbox-control" data-lightbox-zoom-reset aria-label="بازگشت به اندازه اصلی">۱:۱</button>
                                <button type="button" class="product-lightbox-control" data-lightbox-zoom-in aria-label="بزرگ‌نمایی">+</button>
                            </div>
                        </div>
                        <p class="product-lightbox-help">برای بزرگ‌نمایی از دو انگشت یا دکمه‌های + و − استفاده کنید.</p>
                    </dialog>
                @else
                    <div class="product-gallery-stage product-gallery-empty">
                        <div class="organic-lines flex h-full w-full items-center justify-center" aria-label="تصویر این محصول هنوز ثبت نشده است">
                            <svg class="h-32 w-32 text-[#688872]" viewBox="0 0 120 120" fill="none" aria-hidden="true">
                                <path d="m5 89 38-61 26 37 16-24 30 48M14 97h92" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <span class="product-gallery-category">{{ $product->category->name }}</span>
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

                <section class="mt-8" aria-labelledby="product-variants">
                    <h2 id="product-variants" class="text-xl font-extrabold text-[#264c38]">انتخاب بسته و تعداد</h2>
                    @if ($product->variants->isNotEmpty())
                        <form method="POST" action="{{ route('cart.add') }}" class="mt-5">
                            @csrf
                            <fieldset>
                                <legend class="mb-3 text-sm font-semibold text-[#627565]">یکی از بسته‌های زیر را انتخاب کنید:</legend>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    @foreach ($product->variants as $variant)
                                        @php
                                            $quantity = rtrim(rtrim((string) $variant->sale_quantity, '0'), '.');
                                            $unit = match ($variant->sale_unit) {
                                                'g' => 'گرم', 'kg' => 'کیلوگرم', 'l' => 'لیتر',
                                                'piece' => 'عدد', 'pack' => 'بسته',
                                                default => $variant->sale_unit,
                                            };
                                        @endphp
                                        <label class="block cursor-pointer">
                                            <input type="radio" name="variant_id" value="{{ $variant->id }}" required
                                                   class="peer sr-only" data-variant-photo-index="{{ $variantPhotoIndexes[$variant->id] ?? '' }}" @checked($loop->first)>
                                            <span class="store-variant-card flex min-h-28 flex-col justify-between rounded-2xl border-2 border-[#cbd4c7] bg-[#f8f7ef] p-4 transition peer-checked:border-[#245840] peer-checked:bg-[#e4eddf] peer-focus-visible:outline peer-focus-visible:outline-4 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-[#ae855a]">
                                                <span class="flex items-start justify-between gap-3">
                                                    <span class="text-sm font-bold text-[#234835]">{{ $variant->title }}</span>
                                                    <span class="text-xs text-[#677c69]">{{ $quantity }} {{ $unit }}</span>
                                                </span>
                                                <span class="mt-4 text-lg font-extrabold text-[#1c4432]">
                                                    {{ \App\Support\Money::formatToman((int) $variant->price_irr) }}
                                                    <span class="text-xs font-medium">تومان</span>
                                                </span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                            <div class="mt-5 flex flex-col gap-4 rounded-2xl border border-[#cbd4c7] bg-[#e6ebdd] p-4 sm:flex-row sm:items-end">
                                <div class="flex items-center justify-between gap-4 sm:block">
                                    <label for="product-quantity" class="block text-sm font-bold text-[#294936]">تعداد بسته</label>
                                    <input id="product-quantity" name="quantity" type="number" min="1" max="1000"
                                           inputmode="numeric" value="1" required
                                           class="mt-1 min-h-12 w-24 rounded-xl border border-[#9eb19e] bg-white p-3 text-center font-bold">
                                </div>
                                <button type="submit" class="min-h-14 flex-1 rounded-xl bg-[#19392f] px-6 py-4 text-base font-extrabold text-white hover:bg-[#315844] focus-visible:outline focus-visible:outline-4 focus-visible:outline-offset-2 focus-visible:outline-[#ae855a]">
                                    افزودن به سبد خرید
                                </button>
                            </div>
                        </form>
                        <a href="{{ route('cart.index') }}" class="mt-4 inline-flex min-h-11 items-center text-sm font-bold text-[#315743] underline underline-offset-4">مشاهده سبد خرید</a>
                    @else
                        <p class="mt-4 rounded-2xl bg-[#f8e9e4] p-5 text-sm leading-7 text-[#863c30]">فعلاً بسته‌ای برای سفارش این محصول موجود نیست.</p>
                    @endif
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

                <p class="mt-8 text-xs leading-7 text-[#728272]">هزینه ارسال در مرحله تکمیل سفارش مشخص می‌شود. پس از ثبت سفارش، پرداخت با زرین‌پال یا کارت‌به‌کارت امکان‌پذیر است.</p>
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
