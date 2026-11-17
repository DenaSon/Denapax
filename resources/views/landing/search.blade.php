@component('layouts.store', ['title' => 'جستجوی محصولات'])
    <section class="store-search-page" aria-labelledby="search-title">
        <div class="mb-8">
            <p class="store-eyebrow">جستجو در دناپکس</p>
            <h1 id="search-title" class="store-heading mt-2">محصول موردنظرتان را پیدا کنید</h1>
            <p class="store-muted mt-2 max-w-2xl text-sm leading-8">
                نام محصول، نوع خشکبار یا سوغات موردنظرتان را جستجو کنید.
            </p>
        </div>

        <form method="GET" action="{{ route('search') }}" role="search"
              x-data="storeSearch" x-on:submit.prevent="submit($event)"
              class="mb-9 flex flex-col gap-3 sm:flex-row">
            <label for="store-search-query" class="sr-only">جستجو در محصولات</label>
            <input id="store-search-query" name="q" type="search" maxlength="80" value="{{ $search }}"
                   placeholder="مثلاً گردو، شیره انگور یا آلو بخارا"
                   class="landing-search-input min-w-0 flex-1 rounded-xl border border-[#cbd3c3] bg-white px-5 py-3 text-sm text-[#244534] outline-offset-2">
            <button type="submit" class="landing-search-button inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-[#19392f] px-7 py-3 text-sm font-bold text-white">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5" stroke-linecap="round"/></svg>
                جستجو
            </button>
        </form>

        @if ($search === '')
            <div class="store-search-empty">
                <span class="store-search-empty-icon" aria-hidden="true">⌕</span>
                <h2>جستجوی خود را شروع کنید</h2>
                <p>برای دیدن محصولات، عبارت موردنظرتان را در کادر بالا بنویسید.</p>
                <a wire:navigate href="{{ route('home') }}#products" class="store-button store-button-outline mt-5">مشاهده همه محصولات</a>
            </div>
        @else
            <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-lg font-extrabold text-[#214633]">نتایج جستجو</h2>
                    <p class="mt-1 text-xs text-[#758273]">
                        برای «{{ $search }}» {{ $products->total() }} محصول پیدا شد.
                    </p>
                </div>
                <a wire:navigate href="{{ route('home') }}#products" class="text-xs font-bold text-[#42624e] underline underline-offset-4">بازگشت به فروشگاه</a>
            </div>

            @if ($products->isNotEmpty())
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 lg:gap-7">
                    @foreach ($products as $product)
                        @include('landing.partials.product-card', ['product' => $product])
                    @endforeach
                </div>

                @if ($products->hasPages())
                    <nav class="mt-10 flex items-center justify-between border-t border-[#d8dfd3] pt-6 text-sm font-semibold" aria-label="صفحات نتایج جستجو">
                        @if ($products->previousPageUrl())
                            <a wire:navigate href="{{ $products->previousPageUrl() }}" class="text-[#315743] hover:text-[#19392f]">صفحه قبل ←</a>
                        @else
                            <span class="text-[#a2ada0]">صفحه قبل</span>
                        @endif
                        <span class="text-xs text-[#718276]">صفحه {{ $products->currentPage() }} از {{ $products->lastPage() }}</span>
                        @if ($products->nextPageUrl())
                            <a wire:navigate href="{{ $products->nextPageUrl() }}" class="text-[#315743] hover:text-[#19392f]">→ صفحه بعد</a>
                        @else
                            <span class="text-[#a2ada0]">صفحه بعد</span>
                        @endif
                    </nav>
                @endif
            @else
                <div class="store-search-empty">
                    <span class="store-search-empty-icon" aria-hidden="true">⌕</span>
                    <h2>محصولی پیدا نشد</h2>
                    <p>عبارت دیگری را امتحان کنید یا فهرست همه محصولات را ببینید.</p>
                    <a wire:navigate href="{{ route('home') }}#products" class="store-button store-button-outline mt-5">مشاهده همه محصولات</a>
                </div>
            @endif
        @endif
    </section>
@endcomponent
