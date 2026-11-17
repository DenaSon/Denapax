<?php

namespace App\Integrations\Sms;

use GuzzleHttp\Client;
use Ipe\Sdk\SmsIrService;

/**
 * Uses the official SMS.ir SDK while supplying config-cache-safe credentials
 * and bounded HTTP timeouts. The SDK's package provider is not auto-discovered.
 */
class ConfiguredSmsIrService extends SmsIrService
{
    private const BASE_URI = 'https://api.sms.ir/v1/';

    public function __construct(string $apiKey, float $timeout, float $connectTimeout)
    {
        parent::__construct($apiKey, self::BASE_URI);

        $this->client = new Client([
            'base_uri' => self::BASE_URI,
            'headers' => [
                'X-API-KEY' => $apiKey,
                'Accept' => 'application/json',
            ],
            'timeout' => $timeout,
            'connect_timeout' => $connectTimeout,
            'allow_redirects' => false,
        ]);
    }
}
