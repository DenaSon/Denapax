<?php

use App\Http\Middleware\ForceHttpsUrls;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust only configured ingress peers. ForceHttpsUrls fixes generated links,
        // but does not mark a forwarded HTTPS request as secure for cookies/CSRF.
        // Do not trust X-Forwarded-Host or X-Forwarded-For from public clients.
        $trustedProxies = trim((string) config('app.trusted_proxies', ''));
        if ($trustedProxies !== '') {
            $middleware->trustProxies(
                at: $trustedProxies,
                headers: Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_PORT,
            );
        }

        // Keep HTTPS link generation for ingress configurations without a
        // correct forwarded scheme; do not redirect internal HTTP proxy hops.
        $middleware->append(ForceHttpsUrls::class);
        $middleware->redirectGuestsTo(fn (Request $request): string => $request->is('checkout', 'checkout/*', 'orders/*', 'account/*')
            ? route('customer.login')
            : route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
