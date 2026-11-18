@component('layouts.store', ['title' => 'ورود پیامکی'])
    <section class="store-card mx-auto max-w-xl p-5 sm:p-9">
        <p class="store-eyebrow">ورود سریع</p>
        @if (session('customer_otp_mobile'))
            <div
                x-data="otpChallenge"
                data-otp-current-mobile="{{ session('customer_otp_mobile') }}"
                data-otp-entered-mobile="{{ old('mobile', session('customer_otp_mobile')) }}"
                data-otp-server-now="{{ $otpServerNow }}"
                data-otp-expires-at="{{ $otpExpiresAt ?? '' }}"
                data-otp-resend-at="{{ $otpResendAt ?? '' }}"
                data-otp-code-usable="{{ $otpCodeUsable ? '1' : '0' }}"
            >
                <h1 class="store-heading mt-2">تأیید شماره موبایل</h1>
                <p class="store-muted mt-3 text-sm">
                    کد {{ $otpLength === 4 ? 'چهاررقمی' : 'شش‌رقمی' }} ارسال‌شده به <strong dir="ltr" class="inline-block">{{ session('customer_otp_mobile') }}</strong> را وارد کنید.
                </p>
                <div class="mt-3 space-y-1 rounded-xl border border-[#d6ded4] bg-[#f7f9f6] px-4 py-3">
                    <p class="store-muted text-xs leading-6" aria-live="off" x-text="expiryMessage">
                        @if ($otpCodeUsable)
                            کد ورود حداکثر تا {{ (int) ceil($otpTtlSeconds / 60) }} دقیقه پس از ارسال معتبر است.
                        @else
                            کد فعلی منقضی یا قابل استفاده نیست؛ کد تازه دریافت کنید.
                        @endif
                    </p>
                    <p class="store-muted text-xs leading-6" aria-live="off" x-text="resendMessage">
                        @if ($otpResendAt && $otpResendAt > now()->timestamp * 1000)
                            ارسال مجدد برای همین شماره پس از گذشت محدودیت ۶۰ ثانیه‌ای امکان‌پذیر است.
                        @else
                            اکنون می‌توانید برای همین شماره کد تازه دریافت کنید.
                        @endif
                    </p>
                </div>
                <form method="POST" action="{{ route('customer.otp.verify') }}" class="mt-6 space-y-4" data-submit-once>
                    @csrf
                    <label for="code" class="block text-sm font-bold">کد ورود</label>
                    <input id="code" name="code" type="text" dir="ltr" inputmode="numeric" autocomplete="one-time-code"
                           maxlength="{{ $otpLength }}" required autofocus placeholder="{{ str_repeat('-', $otpLength) }}"
                           value="{{ old('code') }}" class="store-field min-h-14 text-center text-xl font-bold tracking-[.4em]">
                    <button type="submit" data-busy-label="در حال بررسی..."
                            class="store-button store-button-primary min-h-14 w-full">تأیید و ادامه خرید</button>
                </form>
                <details class="mt-6 border-t border-[#d6ded4] pt-5">
                    <summary class="min-h-11 cursor-pointer text-sm font-semibold text-[#315743] underline underline-offset-4">
                        کد نرسیده؟ ارسال مجدد یا تغییر شماره
                    </summary>
                    <form method="POST" action="{{ route('customer.otp.request') }}" class="mt-4 space-y-4" data-submit-once>
                        @csrf
                        <label for="mobile" class="block text-sm font-bold">شماره موبایل</label>
                        <input id="mobile" name="mobile" type="tel" dir="ltr" inputmode="tel" autocomplete="tel" required
                               x-model="mobile"
                               value="{{ old('mobile', session('customer_otp_mobile')) }}"
                               class="store-field min-h-12 text-left">
                        <p class="text-xs leading-6 text-[#687c69]">
                            برای تغییر شماره، شماره جدید را وارد کنید. محدودیت ارسال مجدد برای همان شماره در سمت سرور کنترل می‌شود.
                        </p>
                        <button type="submit" data-busy-label="در حال ارسال..."
                                class="store-button store-button-outline min-h-12 w-full disabled:cursor-not-allowed disabled:opacity-55"
                                x-bind:disabled="requestDisabled">
                            ارسال کد جدید
                        </button>
                    </form>
                </details>
            </div>
        @else
            <h1 class="store-heading mt-2">ورود با موبایل</h1>
            <p class="mt-3 text-sm leading-7 text-[#687c69]">برای ادامه خرید فقط شماره موبایل لازم است؛ نیازی به رمز عبور یا ساخت حساب جداگانه نیست.</p>
            <form method="POST" action="{{ route('customer.otp.request') }}" class="mt-6 space-y-4" data-submit-once>
                @csrf
                <label for="mobile" class="block text-sm font-bold">شماره موبایل</label>
                <input id="mobile" name="mobile" type="tel" dir="ltr" inputmode="tel" autocomplete="tel" required autofocus
                       value="{{ old('mobile') }}" placeholder="09123456789"
                       class="store-field min-h-14 text-left">
                <button type="submit" data-busy-label="در حال ارسال..."
                        class="store-button store-button-primary min-h-14 w-full">دریافت کد ورود</button>
            </form>
        @endif
        <p class="store-muted mt-6 text-xs">سبد خرید شما بعد از ورود حفظ می‌شود.</p>
    </section>
@endcomponent
