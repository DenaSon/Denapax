<div class="store-address-editor">
    @php
        $selectedProvince = \App\Support\IranProvinces::normalize((string) old('province', $province));
        $legacyProvince = $selectedProvince !== '' && ! \App\Support\IranProvinces::contains($selectedProvince);
    @endphp

    <div class="store-address-heading mb-6">
        <p class="store-eyebrow">نشانی تحویل</p>
        <h1 class="store-heading mt-2">{{ $addressId !== null ? 'ویرایش نشانی' : 'نشانی جدید' }}</h1>
        <p class="store-muted mt-2 text-sm">اطلاعات لازم برای تحویل را وارد کنید؛ این نشانی برای خریدهای بعدی هم ذخیره می‌شود.</p>
    </div>

    @if ($errors->any())
        <div role="alert" class="store-error-summary mb-5 rounded-xl p-4 text-sm">
            <p class="font-extrabold">چند مورد از اطلاعات نشانی نیاز به اصلاح دارد:</p>
            <ul class="mt-2 list-disc pr-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <p wire:loading.delay.short wire:target="save" role="status"
       class="store-notice mb-4 rounded-xl p-3 text-sm">در حال ذخیره نشانی…</p>

    <form method="POST" action="{{ $addressId !== null ? route('addresses.update', $addressId) : route('addresses.store') }}"
          class="store-card store-address-form p-5 sm:p-8" wire:submit="save">
        @csrf
        @if ($addressId !== null) @method('PUT') @endif
        <input type="hidden" name="return" value="{{ $returnTo }}">

        <section class="store-address-section" aria-labelledby="address-recipient-title">
            <div class="store-address-section-heading">
                <span class="store-address-step" aria-hidden="true">۱</span>
                <div>
                    <h2 id="address-recipient-title">مشخصات گیرنده</h2>
                    <p>نام و شماره‌ای که برای هماهنگی تحویل استفاده می‌شود.</p>
                </div>
            </div>

            <div class="store-address-grid">
                <div class="store-address-control">
                    <label for="recipient_name" class="store-address-label">نام و نام خانوادگی گیرنده <span aria-hidden="true">*</span></label>
                    <input id="recipient_name" name="recipient_name" wire:model="recipient_name" type="text"
                           maxlength="220" required autocomplete="name" aria-required="true"
                           aria-invalid="{{ $errors->has('recipient_name') ? 'true' : 'false' }}"
                           value="{{ old('recipient_name', $recipient_name) }}"
                           class="store-field min-h-12" placeholder="نام گیرنده سفارش">
                    @error('recipient_name')
                        <p id="recipient-name-error" class="store-address-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="store-address-control">
                    <label for="recipient_mobile" class="store-address-label">موبایل گیرنده <span aria-hidden="true">*</span></label>
                    <input id="recipient_mobile" name="recipient_mobile" wire:model="recipient_mobile"
                           type="tel" dir="ltr" inputmode="tel" maxlength="24" required autocomplete="tel"
                           aria-required="true" aria-describedby="recipient-mobile-help"
                           aria-invalid="{{ $errors->has('recipient_mobile') ? 'true' : 'false' }}"
                           value="{{ old('recipient_mobile', $recipient_mobile) }}" placeholder="09123456789"
                           class="store-field min-h-12 text-left">
                    <p id="recipient-mobile-help" class="store-address-help">شماره ورود شما از قبل درج شده و در صورت نیاز قابل تغییر است.</p>
                    @error('recipient_mobile')
                        <p id="recipient-mobile-error" class="store-address-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </section>

        <section class="store-address-section" aria-labelledby="address-location-title">
            <div class="store-address-section-heading">
                <span class="store-address-step" aria-hidden="true">۲</span>
                <div>
                    <h2 id="address-location-title">موقعیت تحویل</h2>
                    <p>ابتدا استان را از فهرست انتخاب کنید و نام شهر را بنویسید.</p>
                </div>
            </div>

            <div class="store-address-grid">
                <div class="store-address-control">
                    <label for="province" class="store-address-label">استان <span aria-hidden="true">*</span></label>
                    <div class="store-address-select-wrap">
                        <select id="province" name="province" wire:model="province"
                                required autocomplete="address-level1" aria-required="true"
                                aria-describedby="province-help"
                                aria-invalid="{{ $errors->has('province') ? 'true' : 'false' }}"
                                class="store-field store-address-select min-h-12">
                            <option value="" disabled @selected($selectedProvince === '')>استان را انتخاب کنید</option>
                            @if ($legacyProvince)
                                <option value="{{ $selectedProvince }}" selected>استان قبلی: {{ $selectedProvince }} (نیاز به اصلاح)</option>
                            @endif
                            @foreach ($provinces as $provinceOption)
                                <option value="{{ $provinceOption }}" @selected($selectedProvince === $provinceOption)>{{ $provinceOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <p id="province-help" class="store-address-help">فهرست شامل ۳۱ استان ایران است.</p>
                    @if ($legacyProvince)
                        <p class="store-address-warning" role="status">استان قبلی «{{ $selectedProvince }}» در فهرست نیست. بدون تغییر اطلاعات فعلی، یک استان معتبر انتخاب کنید.</p>
                    @endif
                    @error('province')
                        <p id="province-error" class="store-address-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="store-address-control">
                    <label for="city" class="store-address-label">شهر <span aria-hidden="true">*</span></label>
                    <input id="city" name="city" wire:model="city" type="text"
                           maxlength="100" required autocomplete="address-level2" aria-required="true"
                           aria-invalid="{{ $errors->has('city') ? 'true' : 'false' }}"
                           value="{{ old('city', $city) }}" placeholder="مثلاً سی‌سخت"
                           class="store-field min-h-12">
                    <p class="store-address-help">نام شهر یا آبادی محل تحویل را وارد کنید.</p>
                    @error('city')
                        <p id="city-error" class="store-address-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </section>

        <section class="store-address-section" aria-labelledby="address-details-title">
            <div class="store-address-section-heading">
                <span class="store-address-step" aria-hidden="true">۳</span>
                <div>
                    <h2 id="address-details-title">جزئیات نشانی</h2>
                    <p>آدرس دقیق و کد پستی به تحویل درست سفارش کمک می‌کند.</p>
                </div>
            </div>

            <div class="store-address-control">
                <label for="address_line" class="store-address-label">نشانی دقیق <span aria-hidden="true">*</span></label>
                <textarea id="address_line" name="address_line" wire:model="address_line" rows="3"
                          minlength="8" maxlength="2000" required autocomplete="street-address"
                          aria-required="true" aria-invalid="{{ $errors->has('address_line') ? 'true' : 'false' }}"
                          placeholder="خیابان، کوچه، پلاک، طبقه و واحد"
                          class="store-field store-address-textarea">{{ old('address_line', $address_line) }}</textarea>
                @error('address_line')
                    <p id="address-line-error" class="store-address-error" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div class="store-address-grid store-address-details-grid">
                <div class="store-address-control">
                    <label for="postal_code" class="store-address-label">کد پستی ده‌رقمی <span aria-hidden="true">*</span></label>
                    <input id="postal_code" name="postal_code" wire:model="postal_code" type="text"
                           dir="ltr" inputmode="numeric" maxlength="10" required autocomplete="postal-code"
                           aria-required="true" aria-invalid="{{ $errors->has('postal_code') ? 'true' : 'false' }}"
                           placeholder="1234567890" value="{{ old('postal_code', $postal_code) }}"
                           class="store-field min-h-12 text-left">
                    @error('postal_code')
                        <p id="postal-code-error" class="store-address-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="store-address-control">
                    <label for="label" class="store-address-label">نام نشانی <span class="store-address-optional">(اختیاری)</span></label>
                    <input id="label" name="label" wire:model="label" type="text" maxlength="80"
                           aria-invalid="{{ $errors->has('label') ? 'true' : 'false' }}"
                           value="{{ old('label', $label) }}" placeholder="مثلاً خانه یا محل کار"
                           class="store-field min-h-12">
                    @error('label')
                        <p id="address-label-error" class="store-address-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </section>

        <div class="store-address-actions">
            <div class="store-address-actions-copy">
                <strong>نشانی آماده ثبت است؟</strong>
                <p>{{ $returnTo === 'checkout' ? 'بعد از ذخیره به تکمیل سفارش برمی‌گردید؛ هنوز پرداختی انجام نمی‌شود.' : 'هر زمان بخواهید می‌توانید این نشانی را ویرایش کنید.' }}</p>
            </div>
            <div class="store-address-buttons">
                <button type="submit" wire:loading.attr="disabled" wire:target="save"
                        class="store-button store-button-primary store-address-save min-h-14">
                    {{ $returnTo === 'checkout' ? 'ذخیره و ادامه ثبت سفارش' : 'ذخیره نشانی' }}
                </button>
                <a wire:navigate href="{{ $returnTo === 'checkout' ? route('checkout.index') : route('addresses.index') }}"
                   class="store-inline-link store-address-cancel justify-center px-4">انصراف</a>
            </div>
        </div>
    </form>
</div>
