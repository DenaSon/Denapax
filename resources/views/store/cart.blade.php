@component('layouts.store', ['title' => 'سبد خرید'])
    <section class="rounded-3xl bg-white p-6 shadow-sm md:p-10">
        <h1 class="text-2xl font-bold">سبد خرید</h1>
        @forelse ($lines as $line)
            <article class="flex flex-wrap items-center justify-between gap-4 border-b border-[#d6ded4] py-6">
                <div>
                    <a class="font-bold underline" href="{{ route('products.show', $line['variant']->product->slug) }}">{{ $line['variant']->product->title }}</a>
                    <p class="mt-1 text-sm text-[#687c69]">{{ $line['variant']->title }}</p>
                    @if (! $line['available'])<p class="text-sm text-red-700">این تنوع اکنون قابل خرید نیست؛ آن را حذف کنید.</p>@endif
                    <p class="mt-2 text-sm">{{ \App\Support\Money::formatToman($line['total_irr']) }} تومان</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <form method="POST" action="{{ route('cart.update', $line['variant']->id) }}" class="flex items-center gap-2">
                        @csrf @method('PATCH')
                        <label for="qty-{{ $line['variant']->id }}" class="text-sm">تعداد</label>
                        <input id="qty-{{ $line['variant']->id }}" name="quantity" type="number" min="1" max="1000"
                               value="{{ $line['quantity'] }}" class="w-20 rounded-lg border p-2">
                        <button type="submit" class="rounded-lg border px-3 py-2 text-sm">به‌روزرسانی</button>
                    </form>
                    <form method="POST" action="{{ route('cart.remove', $line['variant']->id) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-sm text-red-700">حذف</button>
                    </form>
                </div>
            </article>
        @empty
            <p class="py-10 text-[#687c69]">سبد خرید خالی است.</p>
        @endforelse
        <div class="mt-8 flex flex-wrap items-center justify-between gap-4">
            <p class="text-lg font-bold">جمع کالاها: {{ \App\Support\Money::formatToman((int) $subtotal) }} تومان</p>
            <a href="{{ route('home') }}#products" class="rounded-full border border-[#19392f] px-5 py-3 text-sm">ادامه انتخاب محصولات</a>
        </div>
        @if ($lines->isNotEmpty())
            <p class="mt-6 text-sm text-[#687969]">هزینه ارسال پس از انتخاب نشانی و براساس وزن اقلام محاسبه می‌شود.</p>
            @if ($lines->contains(fn ($line) => ! $line['available']))
                <p class="mt-4 rounded-xl bg-red-50 p-4 text-sm text-red-800">برای ادامه، اقلام ناموجود را حذف کنید.</p>
            @else
                @auth('customer')
                    <a href="{{ route('checkout.index') }}" class="mt-5 inline-block rounded-full bg-[#19392f] px-7 py-3 text-sm font-bold text-white">انتخاب نشانی و ادامه ثبت سفارش</a>
                @else
                    <a href="{{ route('customer.login') }}" class="mt-5 inline-block rounded-full bg-[#19392f] px-7 py-3 text-sm font-bold text-white">ورود با موبایل و ادامه خرید</a>
                @endauth
            @endif
        @endif
    </section>
@endcomponent
