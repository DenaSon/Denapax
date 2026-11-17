<?php

namespace App\Providers;

use App\Http\Middleware\EnsureActiveStaff;
use App\Integrations\Sms\ConfiguredSmsIrService;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Ipe\Sdk\SmsIrService;
use Livewire\Livewire;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The SDK's auto-discovered provider reads env() at resolution time.
        // Bind it here from cached Laravel config and enforce finite HTTP timeouts.
        $this->app->singleton(SmsIrService::class, function ($app): SmsIrService {
            $config = $app['config']->get('services.smsir', []);
            $apiKey = trim((string) ($config['api_key'] ?? ''));

            if ($apiKey === '') {
                throw new RuntimeException('SMS.ir API key is not configured.');
            }

            $timeout = (float) ($config['timeout'] ?? 8);
            $connectTimeout = (float) ($config['connect_timeout'] ?? 3);

            if ($timeout <= 0 || $connectTimeout <= 0 || $connectTimeout > $timeout) {
                throw new RuntimeException('SMS.ir HTTP timeout configuration is invalid.');
            }

            return new ConfiguredSmsIrService($apiKey, $timeout, $connectTimeout);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Re-apply the staff activity check to every Livewire update originating
        // from an admin route, not only to the initial page request.
        Livewire::addPersistentMiddleware([
            EnsureActiveStaff::class,
        ]);

        // UTF8MB4 indexed varchar(255) exceeds some managed MySQL/MariaDB
        // key-size limits (including Runflare's 1000-byte limit).
        Schema::defaultStringLength(191);

        // Service providers boot after configuration has loaded. Do not read
        // config() in bootstrap/app.php's early withMiddleware callback.
        // Trust only known ingress peers and forwarded scheme/port, never
        // forwarded host or client IP headers from arbitrary connections.
        $trustedProxies = trim((string) config('app.trusted_proxies', ''));
        if ($trustedProxies !== '') {
            TrustProxies::at($trustedProxies);
            TrustProxies::withHeaders(Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_PORT);
        }
    }
}
