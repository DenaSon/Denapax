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
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    @if ($photos->isNotEmpty())
        <meta property="og:image" content="{{ $photos->first()['full_url'] }}">
        <meta property="og:image:alt" content="{{ $photos->first()['alt'] }}">
        <meta name="twitter:card" content="summary_large_image">
    @else
        <meta name="twitter:card" content="summary">
    @endif
    <title>{{ $seoTitle }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @if ($photos->isNotEmpty())
        <link rel="preload" as="image" href="{{ $photos->first()['url'] }}">
    @endif
    @vite(['resources/css/landing.css', 'resources/js/storefront.js'])
    @livewireStyles
    <script type="application/ld+json">@json($schema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE)</script>
    <script type="application/ld+json">@json($breadcrumbSchema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE)</script>
</head>
<body class="landing landing-shell antialiased">
    <a href="#product-content" class="sr-only focus:not-sr-only focus:fixed focus:z-50 focus:top-3 focus:right-3 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2">رفتن به جزئیات محصول</a>

    <header class="sticky top-0 z-50 bg-[#17382d] text-[#f4f0e7] shadow-lg">
        <div class="landing-container flex min-h-16 items-center justify-between gap-3 py-2 md:min-h-20 md:gap-4 md:py-3">
            <a wire:navigate href="{{ route('home') }}" class="inline-flex items-center gap-3 rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#e8d3b1]" aria-label="بازگشت به صفحه اصلی دناپکس">
                <svg class="h-7 w-8 md:h-9 md:w-11" viewBox="0 0 70 48" fill="none" aria-hidden="true">
                    <path d="M3 40 26 8l15 22 9-13 17 23" stroke="#E6E8D4" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="m19 18 7-10 10 15-8-5-6 8" fill="#E6E8D4"/>
                    <path d="M3 46h64" stroke="#E6E8D4" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <span class="text-base font-extrabold tracking-tight md:text-xl" lang="en">DenaPax</span>
            </a>
            <nav aria-label="ناوبری محصول" class="flex flex-wrap items-center gap-2 text-xs font-semibold text-[#e6e8d4] sm:gap-5 sm:text-sm">
                <a wire:navigate class="transition hover:text-white" href="{{ route('home') }}#products">همه محصولات</a>
                <a wire:navigate class="hidden md:inline-flex transition hover:text-white" href="{{ route('home') }}#story">روایت دنا</a>
                <a wire:navigate class="inline-flex items-center gap-2 rounded-full border border-white/40 px-3 py-2 transition hover:bg-white hover:text-[#19392f] sm:px-4" href="{{ route('cart.index') }}">سبد خرید <livewire:store.cart-count /></a>
            </nav>
        </div>
    </header>

    <main id="product-content" class="landing-container product-page pb-32 pt-5 md:pb-16 md:pt-7">
        <nav aria-label="مسیر صفحه" class="product-breadcrumb mb-5 flex flex-wrap items-center gap-2 text-xs text-[#627664]">
            <a wire:navigate class="hover:text-[#1d4130]" href="{{ route('home') }}">خانه</a>
            <span aria-hidden="true">/</span>
            <a wire:navigate class="hover:text-[#1d4130]" href="{{ route('categories.show', ['slug' => $product->category->slug]) }}">{{ $product->category->name }}</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page" class="font-semibold text-[#244633]">{{ $product->title }}</span>
        </nav>

        <div class="product-layout" dir="ltr">
            <div class="product-gallery-column min-w-0" data-product-gallery dir="rtl" aria-label="گالری تصاویر {{ $product->title }}">
                @if ($photos->isNotEmpty())
                    <div class="product-gallery-stage" data-gallery-stage>
                        <div class="product-gallery-main">
                            <img data-gallery-main class="product-gallery-main-image"
                                 src="{{ $photos->first()['url'] }}" alt="{{ $photos->first()['alt'] }}"
                                 width="960" height="960" fetchpriority="high" decoding="async">
                        </div>
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
                                <button type="button" class="product-gallery-thumb"
                                        data-gallery-thumb data-gallery-index="{{ $loop->index }}"
                                        data-image-url="{{ $photo['url'] }}"
                                        data-image-alt="{{ $photo['alt'] }}"
                                        aria-label="انتخاب تصویر {{ $loop->iteration }} از {{ $photos->count() }}"
                                        @if ($loop->first) aria-current="true" @endif>
                                    <img src="{{ $photo['thumb_url'] }}" alt="" width="192" height="192" loading="lazy" decoding="async">
                                </button>
                            @endforeach
                        </div>
                    @else
                        <button type="button" data-gallery-thumb data-gallery-index="0"
                                data-image-url="{{ $photos->first()['url'] }}"
                                data-image-alt="{{ $photos->first()['alt'] }}" hidden></button>
                    @endif
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
            <article class="product-info-column" dir="rtl"
                     x-data="productPurchase(@js($variantPurchaseData), @js($cartQuantities))"
                     x-on:cart-added.window="handleCartAdded($event)"
                     x-on:cart-add-failed.window="handleCartFailed($event)"
                     x-on:cart-quantity-updated.window="handleCartQuantityUpdated($event)">
                <header class="product-intro">
                    <p class="product-eyebrow">{{ $product->category->name }}</p>
                    <h1 class="product-title">{{ $product->title }}</h1>
                    @if ($product->summary)
                        <p class="product-summary">{{ $product->summary }}</p>
                    @endif
                </header>

                @php
                    $firstVariant = $product->variants->first();
                    $initialQuantity = $firstVariant ? max(1, (int) ($cartQuantities->{(string) $firstVariant->id} ?? 0)) : 1;
                    $initialTotalPrice = $firstVariant
                        ? \App\Support\Money::formatToman((int) $firstVariant->price_irr * $initialQuantity)
                        : '0';
                @endphp

                <section class="product-purchase-section" aria-labelledby="product-variants">
                    <div class="product-purchase-heading">
                        <div>
                            <h2 id="product-variants">انتخاب بسته</h2>
                            <p>قیمت هر بسته را ببینید و تعداد موردنظر را انتخاب کنید.</p>
                        </div>
                        <span class="product-purchase-step-hint">انتخاب و سفارش</span>
                    </div>

                    @if ($product->variants->isNotEmpty())
                        <form method="POST" action="{{ route('cart.add') }}" class="product-purchase-form"
                              x-on:submit="handleAddSubmit($event)">
                            @csrf
                            <fieldset class="product-variant-fieldset" x-bind:disabled="busy || needsReconciliation">
                                <legend class="sr-only">انتخاب بسته موردنظر</legend>
                                <div class="product-variant-grid">
                                    @foreach ($product->variants as $variant)
                                        @php
                                            $quantity = rtrim(rtrim((string) $variant->sale_quantity, '0'), '.');
                                            $unit = match ($variant->sale_unit) {
                                                'g' => 'گرم', 'kg' => 'کیلوگرم', 'l' => 'لیتر',
                                                'piece' => 'عدد', 'pack' => 'بسته',
                                                default => $variant->sale_unit,
                                            };
                                        @endphp
                                        <label class="product-variant-option block cursor-pointer">
                                            <input type="radio" name="variant_id" value="{{ $variant->id }}" required
                                                   x-model.number="selectedVariantId"
                                                   x-on:change="clearFeedback()"
                                                   class="peer sr-only" data-variant-photo-index="{{ $variantPhotoIndexes[$variant->id] ?? '' }}" @checked($loop->first)>
                                            <span class="store-variant-card product-variant-card">
                                                <span class="product-variant-card-heading">
                                                    <span class="product-variant-title">{{ $variant->title }}</span>
                                                    <span class="product-variant-amount">{{ $quantity }} {{ $unit }}</span>
                                                </span>
                                                <span class="product-variant-price">
                                                    <strong>{{ \App\Support\Money::formatToman((int) $variant->price_irr) }}</strong>
                                                    <small>تومان / بسته</small>
                                                </span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>

                            <div class="product-purchase-quantity-input" x-show="!hasSelectedInCart">
                                <label for="product-quantity">تعداد بسته</label>
                                <div class="product-purchase-request-stepper" dir="ltr">
                                    <button type="button" x-on:click="adjustRequestedQuantity(-1)"
                                            x-bind:disabled="busy || needsReconciliation || Number(requestedQuantity) <= 1"
                                            aria-label="کاهش تعداد بسته">−</button>
                                    <input id="product-quantity" name="quantity" type="number" min="1" max="1000"
                                           inputmode="numeric" value="1" required x-model.number="requestedQuantity"
                                           x-bind:disabled="busy || needsReconciliation"
                                           aria-label="تعداد بسته برای سفارش">
                                    <button type="button" x-on:click="adjustRequestedQuantity(1)"
                                            x-bind:disabled="busy || needsReconciliation || Number(requestedQuantity) >= 1000"
                                            aria-label="افزایش تعداد بسته">+</button>
                                </div>
                            </div>

                            <p x-show="notice" x-cloak x-text="notice" role="status" class="product-purchase-feedback product-purchase-success"></p>
                            <div x-show="error" x-cloak role="alert" class="product-purchase-feedback product-purchase-error">
                                <p x-text="error"></p>
                                <button type="button" x-show="needsReconciliation" x-cloak
                                        x-on:click="refreshSelectedQuantity()" x-bind:disabled="busy"
                                        class="mt-2 font-bold underline underline-offset-4">
                                    بررسی وضعیت سبد خرید
                                </button>
                                <button type="button" x-show="requiresReload" x-cloak
                                        x-on:click="window.location.reload()"
                                        class="mt-2 font-bold underline underline-offset-4">
                                    تازه‌سازی صفحه
                                </button>
                            </div>

                            <div class="product-purchase-panel" data-product-purchase-panel
                                 x-bind:aria-busy="busy.toString()">
                                <div class="product-purchase-panel-meta">
                                    <span class="product-purchase-panel-label">مبلغ کل</span>
                                    <strong class="product-purchase-panel-price">
                                        <span x-text="selectedPriceLabel">{{ $initialTotalPrice }}</span>
                                        <small>تومان</small>
                                    </strong>
                                    <span class="product-purchase-panel-variant" data-product-selected-variant>
                                        <b x-text="selectedVariantTitle">{{ $firstVariant?->title ?? 'بسته انتخابی' }}</b>
                                        <span>· تعداد <b x-text="selectedQuantityLabel">{{ $initialQuantity }}</b></span>
                                    </span>
                                </div>

                                <div class="product-purchase-panel-actions">
                                    <button type="submit" x-show="!hasSelectedInCart"
                                            x-bind:disabled="busy || needsReconciliation"
                                            class="product-purchase-add-button">
                                        افزودن به سبد خرید
                                    </button>

                                    <details class="product-purchase-fallback" x-show="!hasSelectedInCart && !needsReconciliation">
                                        <summary>اگر افزودن انجام نشد</summary>
                                        <button type="submit" x-on:click="useNativeFallback()">
                                            افزودن با بارگذاری صفحه
                                        </button>
                                    </details>

                                    <div class="product-purchase-stepper" x-show="hasSelectedInCart" x-cloak>
                                        <button type="button" x-on:click="setSelectedQuantity(selectedCartQuantity - 1)"
                                                x-bind:disabled="busy || needsReconciliation" aria-label="کم کردن تعداد از سبد خرید">−</button>
                                        <output aria-label="تعداد فعلی در سبد خرید" x-text="selectedCartQuantity">0</output>
                                        <button type="button" x-on:click="setSelectedQuantity(selectedCartQuantity + 1)"
                                                x-bind:disabled="busy || needsReconciliation || selectedCartQuantity >= 1000"
                                                aria-label="افزایش تعداد در سبد خرید">+</button>
                                        <button type="button" x-on:click="setSelectedQuantity(0)"
                                                x-bind:disabled="busy || needsReconciliation" class="product-purchase-remove">حذف</button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <a wire:navigate href="{{ route('cart.index') }}" class="product-view-cart-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 4h2l2.2 11h10.9L21 8H7M10 20h.01M17 20h.01" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            مشاهده سبد خرید
                            <span aria-hidden="true">←</span>
                        </a>
                    @else
                        <p class="product-unavailable-message">فعلاً بسته‌ای برای سفارش این محصول موجود نیست.</p>
                    @endif
                </section>

                <section class="product-details" aria-label="اطلاعات تکمیلی محصول">
                    @if ($product->description)
                        <section class="product-description" aria-labelledby="product-description-title">
                            <h2 id="product-description-title">درباره محصول</h2>
                            <p>{{ $product->description }}</p>
                        </section>
                    @endif

                    @if ($product->features->isNotEmpty())
                        <section class="product-features" aria-labelledby="product-features-title">
                            <h2 id="product-features-title">مشخصات محصول</h2>
                            <dl class="product-features-grid">
                                @foreach ($product->features as $feature)
                                    <div class="product-feature-row">
                                        <dt>{{ $feature->name }}</dt>
                                        <dd>{{ $feature->value }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </section>
                    @endif

                    <div class="product-delivery-note">
                        <p>هزینه ارسال در مرحله تکمیل سفارش مشخص می‌شود.</p>

                    </div>
                </section>
            </article>
        </div>
    </main>

    <footer class="bg-[#122e26] py-9 text-[#d6dfcf]">
        <div class="landing-container flex flex-wrap items-center justify-between gap-5">
            <a wire:navigate href="{{ route('home') }}" class="text-xl font-extrabold text-white" lang="en">DenaPax</a>
            <span class="text-xs">سوغات و خشکبار؛ از دل دنا</span>
            @include('partials.store-support-contact')
            <span class="text-xs">طراحی و توسعه:
                <a href="https://t.me/m_asadix7" target="_blank" rel="noopener noreferrer" class="text-white hover:underline">Mohammad Asadi</a>
            </span>
        </div>
    </footer>
    <livewire:store.add-to-cart />
    <livewire:store.network-status />
    @livewireScripts
</body>
</html>
