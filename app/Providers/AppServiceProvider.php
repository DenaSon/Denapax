<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // UTF8MB4 indexed varchar(255) exceeds some managed MySQL/MariaDB
        // key-size limits (including Runflare's 1000-byte limit).
        Schema::defaultStringLength(191);

        // Runflare (and similar TLS-terminating proxies) can forward an HTTPS
        // browser request to PHP over HTTP. Livewire and logout use url()/route(),
        // not ASSET_URL, so honor our explicit canonical production HTTPS URL.
        if (config('app.env') === 'production'
            && parse_url((string) config('app.url'), PHP_URL_SCHEME) === 'https') {
            URL::forceScheme('https');
        }
    }
}
