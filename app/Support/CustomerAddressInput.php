<?php

namespace App\Support;

use Illuminate\Validation\Rule;

class CustomerAddressInput
{
    public static function rules(): array
    {
        return [
            'label' => 'nullable|string|max:80',
            'recipient_name' => 'required|string|max:220',
            'recipient_mobile' => 'required|string|max:24',
            'province' => ['required', 'string', 'max:100', Rule::in(IranProvinces::all())],
            'city' => 'required|string|max:100',
            'address_line' => 'required|string|min:8|max:2000',
            'postal_code' => 'required|string|max:16',
        ];
    }

    public static function messages(): array
    {
        return [
            'province.required' => 'لطفاً استان را انتخاب کنید.',
            'province.in' => 'استان انتخاب‌شده معتبر نیست؛ لطفاً از فهرست استان‌ها انتخاب کنید.',
            'city.required' => 'لطفاً نام شهر را وارد کنید.',
        ];
    }

    public static function normalize(array $attributes): array
    {
        if (isset($attributes['province']) && is_string($attributes['province'])) {
            $attributes['province'] = IranProvinces::normalize($attributes['province']);
        }

        if (isset($attributes['city']) && is_string($attributes['city'])) {
            $attributes['city'] = trim($attributes['city']);
        }

        if (isset($attributes['recipient_mobile']) && is_string($attributes['recipient_mobile'])) {
            $attributes['recipient_mobile'] = IranMobile::normalize($attributes['recipient_mobile']);
        }

        if (isset($attributes['postal_code']) && is_string($attributes['postal_code'])) {
            $attributes['postal_code'] = IranDigits::toAscii($attributes['postal_code']);
        }

        return $attributes;
    }
}
