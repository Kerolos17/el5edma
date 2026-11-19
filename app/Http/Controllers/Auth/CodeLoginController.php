<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\CodeLoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class CodeLoginController extends Controller
{
    /**
     * Maximum cumulative failed attempts before a hard lockout (per IP).
     * The route-level throttle (5/min) handles burst attacks; this second
     * tier catches persistent low-rate brute-force.
     */
    private const MAX_ATTEMPTS = 10;

    /**
     * Hard-lockout decay window in seconds (15 minutes).
     */
    private const DECAY_SECONDS = 900;

    /**
     * System-wide cap on failed code attempts across ALL IPs. A 6-digit code
     * has only 1M values, so an attacker rotating IPs could otherwise guess
     * freely against every active account at once. The counter is never
     * cleared by a successful login (an attacker could interleave a valid
     * login to reset it); it only expires with time.
     */
    private const GLOBAL_MAX_ATTEMPTS = 30;

    private const GLOBAL_DECAY_SECONDS = 3600;

    private const GLOBAL_LIMITER_KEY = 'code-login-global';

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            // Numeric codes (4-6 digits); legacy formats stay within max:12.
            'code' => ['required', 'string', 'min:4', 'max:12'],
        ]);

        if (RateLimiter::tooManyAttempts(self::GLOBAL_LIMITER_KEY, self::GLOBAL_MAX_ATTEMPTS)) {
            return back()->withErrors([
                'code' => __('auth.lockout', ['seconds' => RateLimiter::availableIn(self::GLOBAL_LIMITER_KEY)]),
            ]);
        }

        $limiterKey = 'code-login|' . $request->ip();

        if (RateLimiter::tooManyAttempts($limiterKey, self::MAX_ATTEMPTS)) {
            $available = RateLimiter::availableIn($limiterKey);

            return back()->withErrors([
                'code' => __('auth.lockout', ['seconds' => $available]),
            ]);
        }

        $user = app(CodeLoginService::class)->attempt($request->input('code', ''));

        if (! $user) {
            RateLimiter::hit($limiterKey, self::DECAY_SECONDS);
            RateLimiter::hit(self::GLOBAL_LIMITER_KEY, self::GLOBAL_DECAY_SECONDS);

            return back()->withErrors([
                'code' => __('auth.invalid_code'),
            ]);
        }

        // Successful login — clear the failed-attempt counter.
        RateLimiter::clear($limiterKey);

        Auth::login($user); // No remember-me; sessions expire on browser close for security.
        $request->session()->regenerate();

        $user->update(['last_login_at' => now()]);

        App::setLocale($user->locale ?? 'ar');

        return redirect()->route($user->homeRoute());
    }
}
