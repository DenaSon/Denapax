@component('layouts.store', [
    'title' => $category->name,
    'indexable' => true,
    'canonicalUrl' => $canonicalUrl,
    'seoTitle' => $seoTitle,
    'metaDescription' => $metaDescription,
    'schema' => $schema,
])
    <nav aria-label="مسیر صفحه" class="mb-6 flex flex-wrap items-center gap-2 text-xs text-[#627664]">
        <a wire:navigate href="{{ route('home') }}" class="store-inline-link">خانه</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page" class="font-bold text-[#244633]">{{ $category->name }}</span>
    </nav>

    <section aria-labelledby="category-title">
        <div class="mb-8">
            <p class="store-eyebrow">دسته‌بندی محصولات دناپکس</p>
            <h1 id="category-title" class="store-heading mt-2">{{ $category->name }}</h1>
            <p class="store-muted mt-3 max-w-2xl text-sm leading-8">
                محصولات دسته {{ $category->name }} را با تصاویر، وزن و قیمت بسته‌های موجود بررسی کنید و برای سفارش انتخاب کنید.
            </p>
            <p class="store-muted mt-3 text-xs">{{ $products->total() }} محصول قابل سفارش</p>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 lg:gap-7">
            @foreach ($products as $product)
                @include('landing.partials.product-card', ['product' => $product])
            @endforeach
        </div>

        @if ($products->hasPages())
            <nav class="mt-10 flex items-center justify-between border-t border-[#d8dfd3] pt-6 text-sm font-semibold" aria-label="صفحات دسته‌بندی">
                @if ($products->previousPageUrl())
                    <a wire:navigate href="{{ $products->previousPageUrl() }}" class="store-inline-link">صفحه قبل ←</a>
                @else
                    <span class="text-[#a2ada0]">صفحه قبل</span>
                @endif
                <span class="text-xs text-[#718276]">صفحه {{ $products->currentPage() }} از {{ $products->lastPage() }}</span>
                @if ($products->nextPageUrl())
                    <a wire:navigate href="{{ $products->nextPageUrl() }}" class="store-inline-link">→ صفحه بعد</a>
                @else
                    <span class="text-[#a2ada0]">صفحه بعد</span>
                @endif
            </nav>
        @endif

        <div class="mt-10 text-sm">
            <a wire:navigate href="{{ route('home') }}#products" class="store-inline-link">مشاهده همه محصولات دناپکس</a>
        </div>
    </section>
@endcomponent
