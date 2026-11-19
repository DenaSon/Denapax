<?php

namespace App\Integrations\Sms;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

final class MedianaPaymentApprovalSmsSender
{
    private const NORMAL_ENDPOINT = 'https://api.mediana.ir/sms/v1/send/sms';

    private const PATTERN_ENDPOINT = 'https://api.mediana.ir/sms/v1/send/pattern';

    public function send(string $mobile, string $orderReference): void
    {
        $apiKey = trim((string) config('services.mediana.api_key', ''));
        $timeout = (float) config('services.mediana.timeout', 8);
        $connectTimeout = (float) config('services.mediana.connect_timeout', 3);
        $pattern = trim((string) config('services.mediana.payment_approval_pattern_code', ''));
        $parameter = trim((string) config('services.mediana.payment_approval_pattern_parameter', 'ORDER'));

        if ($apiKey === '' || ! preg_match('/^09[0-9]{9}$/D', $mobile)
            || ! preg_match('/^[A-Za-z0-9_-]{1,40}$/D', $orderReference)
            || $timeout <= 0 || $connectTimeout <= 0 || $connectTimeout > $timeout
            || ($pattern !== '' && (! preg_match('/^[A-Za-z0-9_-]{1,100}$/D', $pattern)
                || ! preg_match('/^[A-Za-z0-9_]{1,40}$/D', $parameter)))) {
            throw new RuntimeException('Mediana payment approval SMS is not configured correctly.');
        }

        // The approved pattern is separate from MEDIANA_OTP_PATTERN_CODE.
        $endpoint = $pattern === '' ? self::NORMAL_ENDPOINT : self::PATTERN_ENDPOINT;
        $payload = $pattern === ''
            ? [
                'type' => 'Informational',
                'recipients' => [$mobile],
                'messageText' => "دناپکس\nپرداخت سفارش {$orderReference} تأیید شد. سفارش شما در صف آماده‌سازی قرار گرفت.",
            ]
            : [
                'type' => 'Informational',
                'recipients' => [$mobile],
                'patternCode' => $pattern,
                'parameters' => [$parameter => $orderReference],
            ];

        try {
            // Never follow redirects with API credentials or automatically retry
            // an ambiguous timeout that might have already sent a message.
            $response = Http::acceptJson()
                ->asJson()
                ->withHeaders(['X-API-KEY' => $apiKey])
                ->connectTimeout($connectTimeout)
                ->timeout($timeout)
                ->withOptions(['allow_redirects' => false])
                ->post($endpoint, $payload);
        } catch (Throwable) {
            throw new RuntimeException('Mediana payment approval SMS network request failed.');
        }

        $requestId = $response->json('data.requestId');
        $validRequestId = is_int($requestId)
            || (is_string($requestId) && ctype_digit($requestId));

        // A successful API response means accepted/queued, NOT handset delivery.
        if (! $response->successful()
            || $response->json('meta.code') !== 'OK'
            || $response->json('data.succeed') !== true
            || ! $validRequestId
            || (int) $requestId < 1) {
            // Do not expose response bodies: they may contain personal data.
            $code = $response->json('meta.code');
            $safeCode = is_string($code) && preg_match('/^[A-Za-z0-9_-]{1,40}$/D', $code)
                ? $code : 'unknown';

            throw new RuntimeException('Mediana payment approval SMS was not accepted (HTTP '.$response->status().', code '.$safeCode.').');
        }
    }
}
