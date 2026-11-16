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
        // Run after Laravel's proxy middleware: generate HTTPS URLs even
        // when TLS terminates upstream of PHP-FPM.
        $middleware->append(ForceHttpsUrls::class);
        $middleware->redirectGuestsTo(fn (Request $request): string => $request->is('checkout', 'checkout/*', 'orders/*', 'account/*')
            ? route('customer.login')
            : route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
