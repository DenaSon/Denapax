<div class="contents">
    <div class="hidden items-center gap-4 lg:flex">
        <span class="text-xs tracking-[.04em] text-white/75">سوغات و خشکبار دنا</span>
        <a wire:navigate.hover class="landing-button border border-white/60 text-sm hover:bg-white hover:text-forest"
           href="{{ route('cart.index') }}">
            سبد خرید
            <span class="store-cart-count{{ $quantity > 0 ? '' : ' hidden' }}"
                  aria-label="تعداد اقلام سبد خرید: {{ $quantity }}">{{ $quantity }}</span>
        </a>
    </div>
    <div class="flex items-center lg:hidden">
        <a wire:navigate.hover href="{{ route('cart.index') }}" class="landing-mobile-cart"
           aria-label="سبد خرید{{ $quantity > 0 ? '، '.$quantity.' قلم کالا' : '' }}">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 5h2l2.2 10.5h9.6L20 8H7M10 20h.01M17 20h.01" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span class="store-cart-count{{ $quantity > 0 ? '' : ' hidden' }}"
                  aria-label="تعداد اقلام سبد خرید: {{ $quantity }}">{{ $quantity }}</span>
        </a>
    </div>
</div>
