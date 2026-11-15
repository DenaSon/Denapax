<?php

namespace App\Support;

final class AdminLabels
{
    public static function order(string $status): string
    {
        return [
            'awaiting_payment' => 'در انتظار پرداخت', 'confirmed' => 'آماده پردازش',
            'preparing' => 'در حال آماده‌سازی', 'shipped' => 'ارسال‌شده',
            'delivered' => 'تحویل‌شده', 'canceled' => 'لغوشده',
        ][$status] ?? $status;
    }

    public static function payment(string $status): string
    {
        return [
            'awaiting_receipt' => 'در انتظار پرداخت', 'under_review' => 'در انتظار بررسی رسید',
            'rejected' => 'رسید ردشده', 'approved' => 'پرداخت تأییدشده',
        ][$status] ?? $status;
    }

    public static function receipt(string $status): string
    {
        return [
            'pending' => 'در انتظار بررسی', 'approved' => 'تأییدشده', 'rejected' => 'ردشده',
        ][$status] ?? $status;
    }

    public static function gateway(string $status): string
    {
        return [
            'initializing' => 'در حال ایجاد درخواست', 'request_unknown' => 'پاسخ نامشخص',
            'redirected' => 'ارجاع به بانک', 'verifying' => 'در حال استعلام',
            'verification_pending' => 'نتیجه نامشخص', 'failed' => 'ناموفق؛ نیازمند تطبیق',
            'expired' => 'قدیمی؛ نیازمند تطبیق', 'requires_review' => 'تعارض مالی؛ بررسی فوری',
            'verified' => 'پرداخت تأییدشده', 'cleared_unpaid' => 'عدم واریز تطبیق‌شده',
        ][$status] ?? $status;
    }

    public static function inquiry(string $status): string
    {
        return [
            'PAID' => 'پرداخت‌شده؛ نیازمند تأیید', 'VERIFIED' => 'تأییدشده در درگاه',
            'IN_BANK' => 'در حال پرداخت در بانک', 'FAILED' => 'ناموفق طبق استعلام',
            'REVERSED' => 'برگشت وجه طبق استعلام',
        ][$status] ?? $status;
    }

    public static function notification(string $status): string
    {
        return [
            'failed' => 'ناموفق', 'queued' => 'در صف',
            'sending' => 'در حال ثبت', 'sent' => 'ثبت‌شده',
        ][$status] ?? $status;
    }

    public static function tone(string $status): string
    {
        return match ($status) {
            'approved', 'verified', 'delivered', 'sent', 'cleared_unpaid' => 'success',
            'confirmed', 'preparing', 'shipped' => 'info',
            'rejected', 'failed', 'requires_review', 'canceled' => 'danger',
            default => 'warning',
        };
    }
}
