<?php

use App\Http\Middleware\EnsureAppAccess;
use App\Http\Middleware\EnsureServantAccess;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        channels: __DIR__ . '/../routes/channels.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trust the shared-hosting / load-balancer proxy chain so HTTPS and
        // client IPs resolve correctly. Configure via TRUSTED_PROXIES in .env
        // (comma-separated IPs/CIDRs, or "*" to trust all on cPanel).
        $trustedProxies = env('TRUSTED_PROXIES');
        if (filled($trustedProxies)) {
            $middleware->trustProxies(
                at: $trustedProxies === '*'
                    ? '*'
                    : array_map('trim', explode(',', $trustedProxies)),
            );
        }

        $middleware->web(append: [
            SetLocale::class,
            SecurityHeaders::class,
        ]);

        $middleware->alias([
            'app.access'     => EnsureAppAccess::class,
            'servant.access' => EnsureServantAccess::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('filament.admin.auth.login'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
