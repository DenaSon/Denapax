@php
    $supportContact = trim((string) \App\Models\StoreSetting::query()->value('support_contact'));
    $normalizedContact = strtr($supportContact, [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ]);
    $phone = preg_replace('/[^0-9+]/', '', $normalizedContact);
    $supportHref = filter_var($supportContact, FILTER_VALIDATE_URL)
        ? $supportContact
        : (preg_match('/^\+?[0-9]{7,15}$/D', $phone) ? 'tel:'.$phone : null);
@endphp

@if ($supportContact !== '')
    <span class="store-footer-support">
        <span>پشتیبانی:</span>
        @if ($supportHref)
            <a href="{{ $supportHref }}"
               @if (str_starts_with($supportHref, 'http')) target="_blank" rel="noopener noreferrer" @else dir="ltr" @endif>
                <bdi>{{ $supportContact }}</bdi>
            </a>
        @else
            <bdi>{{ $supportContact }}</bdi>
        @endif
    </span>
@endif
