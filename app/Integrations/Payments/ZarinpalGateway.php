<?php

namespace App\Integrations\Payments;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class ZarinpalGateway
{
    public function enabled(): bool
    {
        return config('zarinpal.enabled') === true && $this->configuredMerchantId() !== null;
    }

    public function request(int $amountIrr, string $callbackUrl, string $reference): string
    {
        $merchantId = $this->configuredMerchantId();

        if (! $this->enabled() || $amountIrr < 1000 || ! str_starts_with($callbackUrl, 'https://')
            && ! (app()->environment(['local', 'testing']) && str_starts_with($callbackUrl, 'http://'))) {
            throw new RuntimeException('Zarinpal payment is not configured for this order.');
        }

        $response = Http::acceptJson()->connectTimeout(3)->timeout(12)->post($this->apiBase().'/pg/v4/payment/request.json', [
            'merchant_id' => $merchantId,
            'amount' => $amountIrr,
            'currency' => 'IRR',
            'callback_url' => $callbackUrl,
            'description' => 'پرداخت سفارش دناپکس '.$reference,
        ]);

        $data = $response->json('data');

        if (! $response->successful() || ! is_array($data) || ($data['code'] ?? null) !== 100
            || ! $this->validAuthority($data['authority'] ?? null)) {
            throw new RuntimeException('Zarinpal could not start the payment.');
        }

        return $data['authority'];
    }

    public function verify(int $amountIrr, string $authority): ?string
    {
        if (! $this->enabled() || $amountIrr < 1000 || ! $this->validAuthority($authority)) {
            throw new RuntimeException('Zarinpal verification parameters are invalid.');
        }

        $response = Http::acceptJson()->connectTimeout(3)->timeout(12)->post($this->apiBase().'/pg/v4/payment/verify.json', [
            'merchant_id' => $this->configuredMerchantId(),
            'amount' => $amountIrr,
            'authority' => $authority,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Zarinpal verification service is unavailable.');
        }

        $data = $response->json('data');

        if (! is_array($data) || ! in_array($data['code'] ?? null, [100, 101], true)) {
            return null;
        }

        $referenceId = (string) ($data['ref_id'] ?? '');

        if (! preg_match('/^[0-9]{1,40}$/D', $referenceId) || $referenceId === '0') {
            throw new RuntimeException('Zarinpal returned an invalid reference.');
        }

        return $referenceId;
    }

    public function paymentUrl(string $authority): string
    {
        if (! $this->validAuthority($authority)) {
            throw new RuntimeException('Invalid gateway authority.');
        }

        return $this->sandbox()
            ? 'https://sandbox.zarinpal.com/pg/StartPay/'.$authority
            : 'https://www.zarinpal.com/pg/StartPay/'.$authority;
    }

    private function apiBase(): string
    {
        return $this->sandbox() ? 'https://sandbox.zarinpal.com' : 'https://api.zarinpal.com';
    }

    private function sandbox(): bool
    {
        return config('zarinpal.sandbox') === true;
    }

    private function configuredMerchantId(): ?string
    {
        $merchantId = (string) config('zarinpal.merchant_id');

        return preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/iD', $merchantId)
            ? $merchantId
            : null;
    }

    private function validAuthority(mixed $authority): bool
    {
        return is_string($authority) && preg_match('/^[A-Za-z0-9]{20,80}$/D', $authority) === 1;
    }
}
