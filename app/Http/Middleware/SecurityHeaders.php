<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = base64_encode(random_bytes(32));

        Vite::useCspNonce($nonce);

        $request->attributes->set('_csp_nonce', $nonce);

        if (class_exists(Livewire::class)) {
            Livewire::useScriptTagAttributes(['nonce' => $nonce]);
        }

        $response = $next($request);

        if (! $response->headers->has('Content-Security-Policy')) {
            $nonceAttr = "'nonce-{$nonce}'";
            $csp = implode('; ', [
                "default-src 'self'",
                "script-src {$nonceAttr} 'unsafe-inline' 'unsafe-eval' 'self' https:",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
                "img-src 'self' data: blob: https:",
                "font-src 'self' https://fonts.gstatic.com data:",
                "connect-src 'self' https://*.pusher.com wss://*.pusher.com https://fcm.googleapis.com https://firebaseinstallations.googleapis.com https://firebaseremoteconfig.googleapis.com https://*.firebaseio.com",
                "frame-ancestors 'self'",
                "form-action 'self'",
                "base-uri 'self'",
            ]);

            $response->headers->set('Content-Security-Policy', $csp);
        }

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
