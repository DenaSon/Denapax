@component('layouts.store', ['title' => 'نشانی‌های من'])
    <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-xs font-semibold tracking-widest text-[#b18056]">ACCOUNT / ADDRESSES</p>
            <h1 class="mt-2 text-3xl font-extrabold">نشانی‌های تحویل</h1>
            <p class="mt-2 text-sm text-[#647565]">برای سفارش‌های بعدی می‌توانید چند نشانی ذخیره کنید.</p>
        </div>
        <a href="{{ route('addresses.create') }}" class="rounded-full bg-[#19392f] px-6 py-3 text-sm font-bold text-white">افزودن نشانی جدید</a>
    </div>
    <div class="grid gap-4 md:grid-cols-2">
        @forelse ($addresses as $address)
            <section class="rounded-2xl border border-[#d9ddd1] bg-white p-6">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="font-bold">{{ $address->label ?: $address->city }}</h2>
                    @if ($address->is_default)
                        <span class="rounded-full bg-[#e1ecdf] px-3 py-1 text-xs text-[#254b32]">پیش‌فرض</span>
                    @endif
                </div>
                <p class="text-sm leading-8">{{ $address->recipient_name }} · <span dir="ltr" class="inline-block">{{ $address->recipient_mobile }}</span></p>
                <p class="mt-2 text-sm leading-8 text-[#687969]">{{ $address->province }}، {{ $address->city }}، {{ $address->address_line }}</p>
                <p class="mt-2 text-xs text-[#687969]">کد پستی: <span dir="ltr">{{ $address->postal_code }}</span></p>
                <div class="mt-6 flex flex-wrap items-center gap-3 border-t border-[#e4e9e1] pt-4 text-sm">
                    <a class="font-semibold underline underline-offset-4" href="{{ route('addresses.edit', $address->id) }}">ویرایش</a>
                    @unless ($address->is_default)
                        <form action="{{ route('addresses.default', $address->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="font-semibold text-[#315d46]">انتخاب به‌عنوان پیش‌فرض</button>
                        </form>
                    @endunless
                    <form action="{{ route('addresses.destroy', $address->id) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="font-semibold text-red-700">حذف</button>
                    </form>
                </div>
            </section>
        @empty
            <div class="rounded-2xl bg-white p-10 text-center text-[#687969] md:col-span-2">
                هنوز نشانی ثبت نشده است.
            </div>
        @endforelse
    </div>
    <a href="{{ route('checkout.index') }}" class="mt-8 inline-block text-sm font-bold underline underline-offset-4">بازگشت به تسویه‌حساب</a>
@endcomponent
