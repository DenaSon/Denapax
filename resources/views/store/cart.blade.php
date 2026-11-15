@component('layouts.store', ['title' => 'سبد خرید'])
    <section class="rounded-2xl bg-white p-5 shadow-sm sm:p-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-extrabold">سبد خرید</h1>
            <a href="{{ route('home') }}#products"
               class="inline-flex min-h-11 items-center text-sm font-semibold underline underline-offset-4">ادامه انتخاب محصولات</a>
        </div>

        @forelse ($lines as $line)
            <article class="flex flex-col justify-between gap-4 border-b border-[#d6ded4] py-5 sm:flex-row sm:items-center">
                <div class="min-w-0">
                    <a class="font-extrabold underline underline-offset-4" href="{{ route('products.show', $line['variant']->product->slug) }}">
                        {{ $line['variant']->product->title }}
                    </a>
                    <p class="mt-1 text-sm text-[#687c69]">{{ $line['variant']->title }}</p>
                    <p class="mt-2 font-bold">{{ \App\Support\Money::formatToman($line['total_irr']) }} تومان</p>
                    @unless ($line['available'])
                        <p class="mt-2 text-sm font-semibold text-red-700">این تنوع در حال حاضر قابل خرید نیست؛ آن را از سبد حذف کنید.</p>
                    @endunless
                </div>
                <div class="flex items-center justify-between gap-3 sm:justify-end">
                    <form method="POST" action="{{ route('cart.update', $line['variant']->id) }}"
                          class="inline-flex items-center gap-2 rounded-xl border border-[#cbd4c7] p-1" aria-label="تغییر تعداد {{ $line['variant']->product->title }}">
                        @csrf @method('PATCH')
                        <button type="submit" name="quantity" value="{{ max(0, $line['quantity'] - 1) }}"
                                class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg bg-[#f0f3e9] text-xl font-bold focus-visible:outline focus-visible:outline-2"
                                aria-label="کم کردن تعداد {{ $line['variant']->product->title }}">−</button>
                        <output class="min-w-8 text-center font-bold" aria-label="تعداد فعلی">{{ $line['quantity'] }}</output>
                        <button type="submit" name="quantity" value="{{ min(1000, $line['quantity'] + 1) }}"
                                class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg bg-[#f0f3e9] text-xl font-bold focus-visible:outline focus-visible:outline-2"
                                aria-label="زیاد کردن تعداد {{ $line['variant']->product->title }}" @disabled($line['quantity'] >= 1000 || ! $line['available'])>+</button>
                    </form>
                    <form method="POST" action="{{ route('cart.remove', $line['variant']->id) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="min-h-11 rounded-lg px-2 text-sm font-bold text-red-700">حذف</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="py-12 text-center">
                <p class="text-base font-semibold">سبد خرید شما خالی است.</p>
                <a href="{{ route('home') }}#products"
                   class="mt-5 inline-flex min-h-12 items-center rounded-full bg-[#19392f] px-6 font-bold text-white">دیدن محصولات دنا</a>
            </div>
        @endforelse

        @if ($lines->isNotEmpty())
            <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-[#d6ded4] pt-5">
                <p class="text-lg font-extrabold">جمع کالاها: {{ \App\Support\Money::formatToman((int) $subtotal) }} تومان</p>
                <p class="text-xs text-[#687969]">هزینه ارسال در مرحله بعد محاسبه می‌شود.</p>
            </div>
            @if ($lines->contains(fn ($line) => ! $line['available']))
                <p role="alert" class="mt-5 rounded-xl bg-red-50 p-4 text-sm leading-7 text-red-800">پیش از ادامه، اقلام ناموجود را حذف کنید.</p>
            @else
                <a href="{{ route('checkout.index') }}"
                   class="mt-6 flex min-h-14 w-full items-center justify-center rounded-2xl bg-[#19392f] px-6 py-4 text-base font-extrabold text-white transition hover:bg-[#2b5741]">
                    @auth('customer')
                        ادامه به نشانی و پرداخت
                    @else
                        ادامه خرید با ورود پیامکی
                    @endauth
                </a>
            @endif
        @endif
    </section>
@endcomponent
