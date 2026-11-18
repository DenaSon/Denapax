<?php

namespace App\Integrations\Sms;

use App\Support\CustomerOtpPolicy;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class MedianaOtpSender
{
    private const ENDPOINT = 'https://api.mediana.ir/sms/v1/send/otp';

    public function send(string $mobile, string $code): void
    {
        $apiKey = trim((string) config('services.mediana.api_key', ''));
        $patternCode = trim((string) config('services.mediana.otp_pattern_code', ''));
        $timeout = (float) config('services.mediana.timeout', 8);
        $connectTimeout = (float) config('services.mediana.connect_timeout', 3);

        if ($apiKey === ''
            || ! preg_match('/^[A-Za-z0-9_-]{1,100}$/D', $patternCode)
            || ! preg_match('/^09[0-9]{9}$/D', $mobile)
            || ! preg_match('/^[0-9]{'.CustomerOtpPolicy::length().'}$/D', $code)
            || $timeout <= 0
            || $connectTimeout <= 0
            || $connectTimeout > $timeout) {
            throw new RuntimeException('Mediana OTP transport is not configured correctly.');
        }

        try {
            // Fixed HTTPS endpoint and no redirects prevent API-key forwarding.
            // Never automatically retry: an ambiguous timeout may already have sent an SMS.
            $response = Http::acceptJson()
                ->asJson()
                ->withHeaders(['X-API-KEY' => $apiKey])
                ->connectTimeout($connectTimeout)
                ->timeout($timeout)
                ->withOptions(['allow_redirects' => false])
                ->post(self::ENDPOINT, [
                    'patternCode' => $patternCode,
                    'recipient' => $mobile,
                    'otpCode' => $code,
                ]);
        } catch (Throwable) {
            // Provider/network errors may contain credentials or plaintext OTP.
            throw new RuntimeException('Mediana OTP request failed.');
        }

        $requestId = $response->json('data.requestId');
        $validRequestId = is_int($requestId)
            || (is_string($requestId) && ctype_digit($requestId));

        // Provider accepted the send request; this is not proof of SMS delivery.
        if (! $response->successful()
            || $response->json('meta.code') !== 'OK'
            || $response->json('data.succeed') !== true
            || ! $validRequestId
            || (int) $requestId < 1) {
            throw new RuntimeException('Mediana OTP request failed.');
        }
    }
}
