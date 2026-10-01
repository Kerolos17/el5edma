<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\JoinRequest;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Single choke point for account states, appended to the web group so it runs
 * on every route: /app, /servant, the Filament panel and the shared
 * auth-only routes (reports, downloads, file access).
 *
 * - Open join request → the applicant keeps their session but every path is
 *   confined to the waiting page (allowlist below); no data is reachable.
 * - Rejected / suspended / legacy inactive → the stale session is destroyed
 *   on the first request. Enforcement is server-side on every request, never
 *   in the UI.
 */
class ConfineInactiveUsers
{
    /** Path prefixes an applicant with an open request may still use. */
    private const ALLOWED_PREFIXES = [
        'registration/status', // the waiting page itself
        'logout',
        'language/',           // own locale only
        'language-guest/',
        'livewire/',           // Livewire update endpoint (incl. the waiting component)
        'fcm-token',           // own-device push registration only
        '_pwa/ping',
        'up',                  // health endpoint
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->is_active) {
            return $next($request);
        }

        if ($user->hasPendingJoinRequest()) {
            $path = $request->path();

            foreach (self::ALLOWED_PREFIXES as $prefix) {
                if ($path === $prefix || str_starts_with($path, $prefix)) {
                    return $next($request);
                }
            }

            return redirect()->route('registration.status');
        }

        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $message = $user->suspended_at !== null
            ? 'تم إيقاف حسابك مؤقتًا. تواصل مع المسؤول.'
            : ($user->joinRequest?->status === JoinRequest::STATUS_REJECTED
                ? 'تم رفض طلب انضمامك. تواصل مع المسؤول.'
                : 'حسابك غير مفعل بعد. تواصل مع المسؤول.');

        return redirect()->route('filament.admin.auth.login')
            ->with('error', $message);
    }
}
