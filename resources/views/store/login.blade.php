@component('layouts.store', ['title' => 'ورود پیامکی'])
    <section class="mx-auto max-w-xl rounded-2xl bg-white p-5 shadow-sm sm:p-9">
        <p class="text-xs font-semibold tracking-widest text-[#a3744e]">ورود سریع</p>
        @if (session('customer_otp_mobile'))
            <h1 class="mt-2 text-2xl font-extrabold">تأیید شماره موبایل</h1>
            <p class="mt-3 text-sm leading-7 text-[#687c69]">
                کد شش‌رقمی ارسال‌شده به <strong dir="ltr" class="inline-block">{{ session('customer_otp_mobile') }}</strong> را وارد کنید.
            </p>
            <form method="POST" action="{{ route('customer.otp.verify') }}" class="mt-6 space-y-4">
                @csrf
                <label for="code" class="block text-sm font-bold">کد ورود</label>
                <input id="code" name="code" type="text" dir="ltr" inputmode="numeric" autocomplete="one-time-code"
                       maxlength="6" required autofocus placeholder="------"
                       value="{{ old('code') }}" class="min-h-14 w-full rounded-xl border border-[#cbd4c7] p-3 text-center text-xl font-bold tracking-[.4em]">
                <button type="submit" class="min-h-14 w-full rounded-2xl bg-[#19392f] px-6 py-4 font-extrabold text-white">تأیید و ادامه خرید</button>
            </form>
            <details class="mt-6 border-t border-[#d6ded4] pt-5">
                <summary class="min-h-11 cursor-pointer text-sm font-semibold text-[#315743] underline underline-offset-4">
                    کد نرسیده؟ ارسال مجدد یا تغییر شماره
                </summary>
                <form method="POST" action="{{ route('customer.otp.request') }}" class="mt-4 space-y-4">
                    @csrf
                    <label for="mobile" class="block text-sm font-bold">شماره موبایل</label>
                    <input id="mobile" name="mobile" type="tel" dir="ltr" inputmode="tel" autocomplete="tel" required
                           value="{{ old('mobile', session('customer_otp_mobile')) }}"
                           class="min-h-12 w-full rounded-xl border border-[#cbd4c7] p-3 text-left">
                    <p class="text-xs leading-6 text-[#687c69]">درخواست مجدد طبق محدودیت زمانی ارسال پیامک انجام می‌شود.</p>
                    <button type="submit" class="min-h-12 w-full rounded-xl border border-[#315743] px-6 font-bold">دریافت کد جدید</button>
                </form>
            </details>
        @else
            <h1 class="mt-2 text-2xl font-extrabold">ورود با موبایل</h1>
            <p class="mt-3 text-sm leading-7 text-[#687c69]">برای ادامه خرید فقط شماره موبایل لازم است؛ نیازی به رمز عبور یا ساخت حساب جداگانه نیست.</p>
            <form method="POST" action="{{ route('customer.otp.request') }}" class="mt-6 space-y-4">
                @csrf
                <label for="mobile" class="block text-sm font-bold">شماره موبایل</label>
                <input id="mobile" name="mobile" type="tel" dir="ltr" inputmode="tel" autocomplete="tel" required autofocus
                       value="{{ old('mobile') }}" placeholder="09123456789"
                       class="min-h-14 w-full rounded-xl border border-[#cbd4c7] p-3 text-left">
                <button type="submit" class="min-h-14 w-full rounded-2xl bg-[#19392f] px-6 py-4 font-extrabold text-white">دریافت کد ورود</button>
            </form>
        @endif
        <p class="mt-6 text-xs leading-7 text-[#687c69]">سبد خرید شما بعد از ورود حفظ می‌شود.</p>
    </section>
@endcomponent
