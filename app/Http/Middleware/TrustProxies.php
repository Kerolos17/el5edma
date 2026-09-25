<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Middleware\TrustProxies as MiddlewareTrustProxies;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrustProxies extends MiddlewareTrustProxies
{
    protected $proxies;

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Comma-separated IPs/CIDRs in TRUSTED_PROXIES, or "*" to trust all
        // (cPanel). Resolved at request time because env() is unreliable
        // once the configuration is cached.
        $proxies = config('app.trusted_proxies');

        if (filled($proxies)) {
            $this->proxies = $proxies === '*'
                ? '*'
                : array_map('trim', explode(',', (string) $proxies));
        }

        return parent::handle($request, $next);
    }
}
