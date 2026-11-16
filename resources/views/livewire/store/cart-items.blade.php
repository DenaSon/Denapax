<section class="store-cart-page"
         wire:loading.class="is-updating"
         wire:loading.attr="aria-busy"
         wire:target="changeQuantity,remove">
    <div class="store-cart-loading" wire:loading.delay.short wire:target="changeQuantity,remove"
         role="status" aria-live="polite">
        <span class="store-cart-spinner" aria-hidden="true"></span>
        <span>در حال به‌روزرسانی سبد خرید…</span>
    </div>

    <header class="store-cart-heading">
        <div class="store-cart-heading-copy">
            <p class="store-cart-eyebrow">خرید از دل دنا</p>
            <h1 class="store-cart-title">سبد خرید</h1>
            <p class="store-cart-description">
                کالاهای انتخاب‌شده را بررسی کنید و برای ادامه، نشانی تحویل را ثبت کنید.
            </p>
        </div>
        <a wire:navigate href="{{ route('home') }}#products" class="store-cart-continue">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M19 12H5m0 0 6 6m-6-6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>ادامه خرید</span>
        </a>
    </header>

    @error('cart')
        <p role="alert" class="store-cart-error">{{ $message }}</p>
    @enderror

    @if ($lines->isEmpty())
        <section class="store-cart-empty">
            <div class="store-cart-empty-icon" aria-hidden="true">
                <svg viewBox="0 0 64 64" fill="none">
                    <path d="M10 14h6l5.5 27.5a5 5 0 0 0 4.9 4h22.2a5 5 0 0 0 4.8-3.7L58 25H20" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="28" cy="54" r="3.5" fill="currentColor"/>
                    <circle cx="48" cy="54" r="3.5" fill="currentColor"/>
                    <path d="M29 10v10m-5-5h10" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                </svg>
            </div>
            <p class="store-cart-empty-kicker">هنوز چیزی انتخاب نکرده‌اید</p>
            <h2>سبد خرید شما خالی است.</h2>
            <p>محصولات محلی دنا را ببینید و هرچه دوست دارید به سبدتان اضافه کنید.</p>
            <a wire:navigate href="{{ route('home') }}#products" class="store-button store-button-primary store-cart-empty-cta">
                مشاهده محصولات
                <span aria-hidden="true">←</span>
            </a>
        </section>
    @else
        <div class="store-cart-grid">
            <section class="store-cart-items-panel" aria-label="اقلام سبد خرید">
                <div class="store-cart-panel-heading">
                    <div>
                        <h2>محصولات انتخاب‌شده</h2>
                        <p>{{ $totalQuantity }} عدد کالا در سبد شما</p>
                    </div>
                    <span class="store-cart-items-count">{{ $lines->count() }} محصول</span>
                </div>

                <div class="store-cart-items-list">
                    @foreach ($lines as $line)
                        @php
                            $product = $line['variant']->product;
                            $cover = $product->getFirstMedia('gallery') ?? $line['variant']->getFirstMedia('variant-image');
                        @endphp
                        <article wire:key="cart-variant-{{ $line['variant']->id }}" class="store-cart-item">
                            <a wire:navigate href="{{ route('products.show', $product->slug) }}"
                               class="store-cart-item-image" aria-label="مشاهده {{ $product->title }}">
                                @if ($cover)
                                    <img src="{{ $cover->getUrl() }}" alt="تصویر {{ $product->title }}"
                                         width="112" height="112" loading="lazy" decoding="async">
                                @else
                                    <span class="store-cart-item-placeholder" aria-hidden="true">
                                        <svg viewBox="0 0 64 64" fill="none">
                                            <path d="m7 49 17-25 11 15 8-11 14 21" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M7 54h50" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                                            <circle cx="44" cy="17" r="5" fill="currentColor" opacity=".45"/>
                                        </svg>
                                    </span>
                                @endif
                            </a>

                            <div class="store-cart-item-details">
                                <div class="store-cart-item-copy">
                                    <a wire:navigate href="{{ route('products.show', $product->slug) }}"
                                       class="store-cart-item-title">{{ $product->title }}</a>
                                    <p class="store-cart-item-variant">{{ $line['variant']->title }}</p>
                                    <p class="store-cart-item-unit-price">
                                        هر بسته
                                        <span>{{ \App\Support\Money::formatToman((int) $line['variant']->price_irr) }} تومان</span>
                                    </p>
                                </div>
                                <div class="store-cart-item-total">
                                    <span>قیمت این ردیف</span>
                                    <strong>{{ \App\Support\Money::formatToman($line['total_irr']) }} <small>تومان</small></strong>
                                </div>
                                @unless ($line['available'])
                                    <p class="store-cart-unavailable">این تنوع در حال حاضر قابل خرید نیست؛ برای ادامه آن را حذف کنید.</p>
                                @endunless
                            </div>

                            <div class="store-cart-item-actions">
                                <form method="POST" action="{{ route('cart.update', $line['variant']->id) }}"
                                      class="store-cart-quantity" aria-label="تغییر تعداد {{ $product->title }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" name="quantity" value="{{ max(0, $line['quantity'] - 1) }}"
                                            wire:click.prevent="changeQuantity({{ $line['variant']->id }}, {{ max(0, $line['quantity'] - 1) }})"
                                            wire:loading.attr="disabled" wire:target="changeQuantity,remove"
                                            aria-label="کم کردن تعداد {{ $product->title }}">−</button>
                                    <output aria-label="تعداد فعلی">{{ $line['quantity'] }}</output>
                                    <button type="submit" name="quantity" value="{{ min(1000, $line['quantity'] + 1) }}"
                                            wire:click.prevent="changeQuantity({{ $line['variant']->id }}, {{ min(1000, $line['quantity'] + 1) }})"
                                            wire:loading.attr="disabled" wire:target="changeQuantity,remove"
                                            aria-label="زیاد کردن تعداد {{ $product->title }}"
                                            @disabled($line['quantity'] >= 1000 || ! $line['available'])>+</button>
                                </form>
                                <form method="POST" action="{{ route('cart.remove', $line['variant']->id) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" wire:click.prevent="remove({{ $line['variant']->id }})"
                                            wire:loading.attr="disabled" wire:target="changeQuantity,remove"
                                            class="store-cart-remove">
                                        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                            <path d="M4.5 6h11m-9.5 0 .6 10h7.8L15 6M8 6V4h4v2m-3 3v4m2-4v4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <span>حذف</span>
                                    </button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>
                <a wire:navigate href="{{ route('home') }}#products" class="store-cart-back-link">
                    <span aria-hidden="true">＋</span>
                    افزودن محصولات دیگر
                </a>
            </section>

            <aside class="store-cart-summary" aria-labelledby="cart-summary-title">
                <div class="store-cart-summary-top">
                    <div>
                        <p class="store-cart-summary-eyebrow">یک قدم تا خرید</p>
                        <h2 id="cart-summary-title">خلاصه سفارش</h2>
                    </div>
                    <span class="store-cart-summary-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M4 5h2l2 10h9l2-7H7m3 11h.01M16 19h.01" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                </div>

                <div class="store-cart-summary-lines">
                    <div>
                        <span>تعداد کالا</span>
                        <strong>{{ $totalQuantity }} عدد</strong>
                    </div>
                    <div>
                        <span>جمع کالاها</span>
                        <strong class="store-amount">{{ \App\Support\Money::formatToman((int) $subtotal) }} تومان</strong>
                    </div>
                    <div>
                        <span>هزینه ارسال</span>
                        <strong class="store-cart-shipping-pending">در مرحله بعد</strong>
                    </div>
                </div>

                <div class="store-cart-summary-total">
                    <span>مبلغ فعلی سفارش</span>
                    <strong class="store-amount">{{ \App\Support\Money::formatToman((int) $subtotal) }} <small>تومان</small></strong>
                </div>

                <p class="store-cart-shipping-note">
                    هزینه ارسال پس از انتخاب نشانی تحویل محاسبه می‌شود و پیش از ثبت نهایی سفارش نمایش داده خواهد شد.
                </p>

                @if ($lines->contains(fn ($line) => ! $line['available']))
                    <p role="alert" class="store-cart-summary-alert">برای ادامه خرید، ابتدا تنوع‌های ناموجود را از سبد حذف کنید.</p>
                    <button type="button" class="store-button store-button-primary store-cart-checkout" disabled>
                        ادامه به نشانی و ارسال
                    </button>
                @else
                    <a wire:navigate href="{{ route('checkout.index') }}" class="store-button store-button-primary store-cart-checkout">
                        <span>
                            @auth('customer')
                                ادامه به نشانی و ارسال
                            @else
                                ادامه خرید با ورود پیامکی
                            @endauth
                        </span>
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M19 12H5m0 0 6 6m-6-6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                @endif

                <div class="store-cart-summary-foot">
                    <span aria-hidden="true">✓</span>
                    <p>مبلغ‌ها به تومان نمایش داده می‌شوند.</p>
                </div>
            </aside>
        </div>
    @endif
</section>
