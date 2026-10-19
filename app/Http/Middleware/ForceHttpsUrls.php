<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class ForceHttpsUrls
{
    /**
     * Generate HTTPS links for a production site whose TLS terminates
     * upstream of PHP-FPM. Do not redirect internal HTTP proxy traffic.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.env') === 'production'
            && parse_url((string) config('app.url'), PHP_URL_SCHEME) === 'https') {
            URL::forceScheme('https');
        }

        return $next($request);
    }
}
