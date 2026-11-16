<?php

namespace App\Support;

class CustomerAddressInput
{
    public static function rules(): array
    {
        return [
            'label' => 'nullable|string|max:80',
            'recipient_name' => 'required|string|max:220',
            'recipient_mobile' => 'required|string|max:24',
            'province' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'address_line' => 'required|string|min:8|max:2000',
            'postal_code' => 'required|string|max:16',
        ];
    }

    public static function normalize(array $attributes): array
    {
        $attributes['recipient_mobile'] = IranMobile::normalize($attributes['recipient_mobile']);
        $attributes['postal_code'] = IranDigits::toAscii($attributes['postal_code']);

        return $attributes;
    }
}
