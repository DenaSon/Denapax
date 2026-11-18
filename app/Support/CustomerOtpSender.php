<?php

namespace App\Support;

use App\Integrations\Sms\MedianaOtpSender;
use App\Integrations\Sms\SmsIrOtpSender;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CustomerOtpSender
{
    public function send(string $mobile, string $code): void
    {
        $driver = (string) config('customer_auth.sms_driver', 'disabled');

        if ($driver === 'mediana') {
            app(MedianaOtpSender::class)->send($mobile, $code);

            return;
        }

        if ($driver === 'smsir') {
            app(SmsIrOtpSender::class)->send($mobile, $code);

            return;
        }

        if ($driver === 'webhook') {
            $url = (string) config('customer_auth.sms_url');
            $token = (string) config('customer_auth.sms_token');

            if (! str_starts_with($url, 'https://') || $token === '') {
                throw new RuntimeException('Secure customer SMS transport is not configured.');
            }

            $response = Http::acceptJson()->withToken($token)->timeout(8)->post($url, [
                'mobile' => $mobile,
                'message' => 'کد ورود دناپکس: '.$code,
            ]);

            if (! $response->successful()) {
                throw new RuntimeException('Customer SMS transport failed.');
            }

            return;
        }

        throw new RuntimeException('Customer SMS transport is disabled.');
    }
}
