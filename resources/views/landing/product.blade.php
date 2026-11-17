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
    @vite(['resources/css/landing.css', 'resources/js/storefront.js'])
    @livewireStyles
    <script type="application/ld+json">@json($schema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE)</script>
</head>
<body class="landing landing-shell antialiased">
    <a href="#product-content" class="sr-only focus:not-sr-only focus:fixed focus:z-50 focus:top-3 focus:right-3 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2">رفتن به جزئیات محصول</a>

    <header class="relative bg-[#17382d] text-[#f4f0e7]">
        <div class="landing-container flex min-h-22 flex-wrap items-center justify-between gap-4 py-4 md:min-h-26">
            <a wire:navigate href="{{ route('home') }}" class="inline-flex items-center gap-3 rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#e8d3b1]" aria-label="بازگشت به صفحه اصلی دناپکس">
                <svg class="h-9 w-11" viewBox="0 0 70 48" fill="none" aria-hidden="true">
                    <path d="M3 40 26 8l15 22 9-13 17 23" stroke="#E6E8D4" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="m19 18 7-10 10 15-8-5-6 8" fill="#E6E8D4"/>
                    <path d="M3 46h64" stroke="#E6E8D4" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <span class="text-xl font-extrabold tracking-tight" lang="en">DenaPax</span>
            </a>
            <nav aria-label="ناوبری محصول" class="flex flex-wrap items-center gap-5 text-xs font-semibold text-[#e6e8d4] sm:text-sm">
                <a wire:navigate class="transition hover:text-white" href="{{ route('home') }}#products">همه محصولات</a>
                <a wire:navigate class="hidden md:inline-flex transition hover:text-white" href="{{ route('home') }}#story">روایت دنا</a>
                <a wire:navigate class="inline-flex items-center gap-2 rounded-full border border-white/40 px-4 py-2 transition hover:bg-white hover:text-[#19392f]" href="{{ route('cart.index') }}">سبد خرید <livewire:store.cart-count /></a>
            </nav>
        </div>
    </header>

    <main id="product-content" class="landing-container pb-40 pt-8 md:pb-32 md:pt-12">
        <nav aria-label="مسیر صفحه" class="mb-10 flex flex-wrap items-center gap-2 text-xs text-[#627664]">
            <a wire:navigate class="hover:text-[#1d4130]" href="{{ route('home') }}">خانه</a>
            <span aria-hidden="true">/</span>
            <a wire:navigate class="hover:text-[#1d4130]" href="{{ route('home') }}?category={{ urlencode($product->category->slug) }}#products">{{ $product->category->name }}</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page" class="font-semibold text-[#244633]">{{ $product->title }}</span>
        </nav>

        <div class="grid items-start gap-9 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,.9fr)] lg:gap-15">
            <div class="min-w-0" data-product-gallery aria-label="گالری تصاویر {{ $product->title }}">
                @if ($photos->isNotEmpty())
                    <div class="product-gallery-stage" data-gallery-stage>
                        <div class="product-gallery-main">
                            <img data-gallery-main class="product-gallery-main-image"
                                 src="{{ $photos->first()['url'] }}" alt="تصویر ۱ از {{ $product->title }}"
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
                                        data-image-alt="تصویر {{ $loop->iteration }} از {{ $product->title }}"
                                        aria-label="انتخاب تصویر {{ $loop->iteration }} از {{ $photos->count() }}"
                                        @if ($loop->first) aria-current="true" @endif>
                                    <img src="{{ $photo['url'] }}" alt="" width="120" height="120" loading="lazy" decoding="async">
                                </button>
                            @endforeach
                        </div>
                    @else
                        <button type="button" data-gallery-thumb data-gallery-index="0"
                                data-image-url="{{ $photos->first()['url'] }}"
                                data-image-alt="تصویر ۱ از {{ $product->title }}" hidden></button>
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
            <article class="min-w-0 lg:pt-6"
                     x-data="productPurchase(@js($variantPurchaseData), @js($cartQuantities))"
                     x-on:cart-added.window="handleCartAdded($event)"
                     x-on:cart-add-failed.window="handleCartFailed($event)"
                     x-on:cart-quantity-updated.window="handleCartQuantityUpdated($event)">
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
                    @php
                        $firstVariant = $product->variants->first();
                        $initialQuantity = $firstVariant ? max(1, (int) ($cartQuantities->{(string) $firstVariant->id} ?? 0)) : 1;
                        $initialTotalPrice = $firstVariant
                            ? \App\Support\Money::formatToman((int) $firstVariant->price_irr * $initialQuantity)
                            : '0';
                    @endphp
                    @if ($product->variants->isNotEmpty())
                        <form method="POST" action="{{ route('cart.add') }}" class="product-purchase-form mt-5"
                              x-on:submit="handleAddSubmit($event)">
                            @csrf
                            <fieldset x-bind:disabled="busy || needsReconciliation">
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
                                        <label class="product-variant-option block cursor-pointer">
                                            <input type="radio" name="variant_id" value="{{ $variant->id }}" required
                                                   x-model.number="selectedVariantId"
                                                   x-on:change="clearFeedback()"
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

                            <div class="product-purchase-quantity-input" x-show="!hasSelectedInCart">
                                <label for="product-quantity" class="text-sm font-bold text-[#294936]">تعداد بسته</label>
                                <input id="product-quantity" name="quantity" type="number" min="1" max="1000"
                                       inputmode="numeric" value="1" required x-model.number="requestedQuantity"
                                       x-bind:disabled="busy || needsReconciliation"
                                       class="min-h-12 w-24 rounded-xl border border-[#9eb19e] bg-white p-3 text-center font-bold">
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
                                    <span class="product-purchase-panel-label">قیمت هر بسته</span>
                                    <strong class="product-purchase-panel-unit-price">
                                        <span x-text="selectedUnitPriceLabel">{{ \App\Support\Money::formatToman((int) $product->variants->first()->price_irr) }}</span>
                                        <small>تومان</small>
                                    </strong>
                                    <span class="product-purchase-panel-label mt-1">جمع قیمت برای <b x-text="selectedQuantity">{{ $initialQuantity }}</b> بسته</span>
                                    <strong class="product-purchase-panel-price">
                                        <span x-text="selectedPriceLabel">{{ $initialTotalPrice }}</span>
                                        <small>تومان</small>
                                    </strong>
                                    <span x-show="hasSelectedInCart" x-cloak class="product-purchase-panel-quantity">
                                        در سبد شما: <b x-text="selectedCartQuantity">0</b>
                                    </span>
                                </div>

                                <div class="product-purchase-panel-actions">
                                    <button type="submit" x-show="!hasSelectedInCart"
                                            x-bind:disabled="busy || needsReconciliation"

                                            class="product-purchase-add-button">
                                        افزودن به سبد خرید
                                    </button>

                                    <details class="product-purchase-fallback" x-show="!hasSelectedInCart && !needsReconciliation">
                                        <summary>مشکل در افزودن دارید؟</summary>
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
                        <a wire:navigate href="{{ route('cart.index') }}" class="mt-4 inline-flex min-h-12 items-center justify-center gap-2 rounded-xl border border-[#315743] bg-white px-5 py-3 text-sm font-extrabold text-[#315743] shadow-sm transition hover:bg-[#edf2e9] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#315743]">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 4h2l2.2 11h10.9L21 8H7M10 20h.01M17 20h.01" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            مشاهده سبد خرید
                        </a>
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

                <p class="mt-8 text-xs leading-7 text-[#728272]">هزینه ارسال در مرحله تکمیل سفارش مشخص می‌شود.</p>
                <x-store.payment-method-note :gateway-enabled="$gatewayEnabled" :manual-available="$manualAvailable" class="mt-3 text-xs leading-7" />
            </article>
        </div>

        <div class="mt-15 border-t border-[#cbd5c4] pt-8">
            <a wire:navigate class="inline-flex items-center gap-3 text-sm font-bold text-[#315743] hover:text-[#1b372b]" href="{{ route('home') }}#products">
                <span aria-hidden="true">→</span>
                بازگشت به همه محصولات
            </a>
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
