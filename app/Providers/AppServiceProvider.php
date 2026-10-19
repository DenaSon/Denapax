<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
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
    }
}
