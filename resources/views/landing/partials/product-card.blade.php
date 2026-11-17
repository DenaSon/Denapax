@php
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
