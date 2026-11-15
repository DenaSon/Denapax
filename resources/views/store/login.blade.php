@component('layouts.store', ['title' => 'ورود مشتری'])
    <section class="rounded-3xl bg-white p-6 shadow-sm md:p-10">
        <h1 class="text-2xl font-bold">ورود به حساب مشتری</h1>
        <p class="mt-3 text-sm text-[#687c69]">برای ادامه خرید شماره موبایل خود را تأیید کنید.</p>
        <form method="POST" action="{{ route('customer.otp.request') }}" class="mt-8 space-y-4">
            @csrf
            <label class="block text-sm font-bold" for="mobile">شماره موبایل</label>
            <input id="mobile" name="mobile" type="tel" dir="ltr" inputmode="tel" autocomplete="tel"
                   value="{{ old('mobile', session('customer_otp_mobile')) }}" required
                   class="w-full rounded-xl border border-[#cbd4c7] p-3 text-left" placeholder="09123456789">
            <button type="submit" class="rounded-full bg-[#19392f] px-6 py-3 font-bold text-white">دریافت کد ورود</button>
        </form>
        @if (session('customer_otp_mobile'))
            <form method="POST" action="{{ route('customer.otp.verify') }}" class="mt-10 space-y-4 border-t border-[#d6ded4] pt-8">
                @csrf
                <p class="text-sm">کد ارسال‌شده به {{ session('customer_otp_mobile') }} را وارد کنید.</p>
                <label class="block text-sm font-bold" for="code">کد شش‌رقمی</label>
                <input id="code" name="code" type="text" dir="ltr" inputmode="numeric" pattern="[0-9]{6}"
                       maxlength="6" autocomplete="one-time-code" required
                       class="w-full rounded-xl border border-[#cbd4c7] p-3 text-left">
                <button type="submit" class="rounded-full bg-[#19392f] px-6 py-3 font-bold text-white">تأیید و ورود</button>
            </form>
        @endif
    </section>
@endcomponent
