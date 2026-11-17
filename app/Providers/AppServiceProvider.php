<?php

namespace App\Providers;

use App\Http\Middleware\EnsureActiveStaff;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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
