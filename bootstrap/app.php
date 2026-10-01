<?php

use App\Http\Middleware\ConfineInactiveUsers;
use App\Http\Middleware\EnsureAppAccess;
use App\Http\Middleware\EnsureServantAccess;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\TrustProxies;
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
        // client IPs resolve correctly. Configured via TRUSTED_PROXIES in
        // .env (comma-separated IPs/CIDRs, or "*" for cPanel). The middleware
        // reads config at request time — env() is unreliable once the config
        // is cached, and this bootstrap runs before config is loaded.
        $middleware->prepend(TrustProxies::class);

        $middleware->web(append: [
            SetLocale::class,
            SecurityHeaders::class,
            // Last line of defense for account states: confines open join
            // requests to the waiting page and destroys rejected/suspended
            // sessions on every web route, including Filament.
            ConfineInactiveUsers::class,
        ]);

        $middleware->alias([
            'app.access'     => EnsureAppAccess::class,
            'servant.access' => EnsureServantAccess::class,
        ]);

        // The maintenance ping has no session — it authenticates with the
        // MAINTENANCE_TOKEN header instead of Laravel's CSRF token.
        $middleware->validateCsrfTokens(except: [
            'maintenance/ping',
        ]);

        $middleware->redirectGuestsTo(fn () => route('filament.admin.auth.login'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
