<div class="home-product-feed" aria-label="فهرست محصولات دناپکس">
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 lg:gap-7" data-home-product-grid>
        @forelse ($products as $product)
            @include('landing.partials.product-card', ['product' => $product, 'homeFeed' => true])
        @empty
            <div class="col-span-full organic-lines rounded-3xl border border-[#d6d9cc] bg-[#e9eadb] p-12 text-center md:p-24">
                <p class="mb-4 text-sm font-semibold text-[#71816c]">COLLECTION / 00</p>
                <h3 class="text-2xl font-bold text-[#254736] md:text-3xl">محصولی برای نمایش پیدا نشد</h3>
                <p class="mt-4 text-sm leading-8 text-[#637366]">محصولات منتشرشده و قابل سفارش به‌صورت خودکار در این بخش نمایش داده می‌شوند.</p>
            </div>
        @endforelse
    </div>

    @if ($canLoadMore)
        @if ($autoload)
            <div wire:key="home-feed-trigger-{{ $loadedBatches }}"
                 wire:intersect.once.margin.200px="loadMore"
                 class="mt-9 flex min-h-14 items-center justify-center gap-3 text-sm text-[#607969]"
                 role="status" aria-live="polite">
                <span class="home-feed-loader" aria-hidden="true"></span>
                <span>در حال بارگذاری محصولات بیشتر…</span>
            </div>
        @else
            <div class="mt-10 flex flex-col items-center gap-3">
                <button type="button" wire:click="loadMore" wire:loading.attr="disabled"
                        wire:target="loadMore"
                        class="home-feed-more rounded-full border border-[#9fb5a2] bg-[#19392f] px-8 py-3 text-sm font-bold text-white transition hover:bg-[#315b43] disabled:opacity-60">
                    نمایش محصولات بیشتر
                </button>
                <p wire:loading wire:target="loadMore" role="status" class="text-xs text-[#607969]">در حال دریافت محصولات…</p>
            </div>
        @endif
    @elseif ($hasMore)
        <p class="mt-9 text-center text-sm text-[#607969]">برای مشاهده محصولات بعدی، از پیوند ادامه فهرست استفاده کنید.</p>
    @elseif ($products->isNotEmpty() && $loadedBatches > 1)
        <p class="mt-9 text-center text-sm text-[#607969]">همه محصولات این فهرست نمایش داده شد.</p>
    @endif

    @if ($hasMore)
        {{-- Bots can follow actual page links without interacting with JavaScript. --}}
        <nav aria-label="پیوند صفحه بعد محصولات" class="sr-only focus-within:not-sr-only">
            <a href="{{ $nextPageUrl }}">صفحه بعد محصولات دناپکس</a>
        </nav>
        <noscript>
            <nav class="mt-8 text-center" aria-label="صفحه‌بندی بدون جاوااسکریپت">
                <a class="inline-flex rounded-full border border-[#9fb5a2] px-6 py-3 text-sm font-bold text-[#19392f]"
                   href="{{ $nextPageUrl }}">مشاهده صفحه بعد محصولات</a>
            </nav>
        </noscript>
    @endif
</div>
