<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\JoinRequest;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAppAccess
{
    private const ALLOWED_ROLES = [
        UserRole::SuperAdmin,
        UserRole::ServiceLeader,
        UserRole::FamilyLeader,
        UserRole::Servant,
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('filament.admin.auth.login');
        }

        if (! $user->is_active) {
            // Applicants with an open join request keep their session but are
            // confined to the waiting page — no data is reachable. Everyone
            // else (rejected / suspended / legacy inactive) is logged out.
            if ($user->hasPendingJoinRequest()) {
                return redirect()->route('registration.status');
            }

            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = $this->inactiveMessage($user);

            return redirect()->route('filament.admin.auth.login')
                ->with('error', $message);
        }

        if (! in_array($user->role, self::ALLOWED_ROLES, true)) {
            abort(403, 'غير مصرح بالوصول إلى التطبيق.');
        }

        return $next($request);
    }

    private function inactiveMessage($user): string
    {
        if ($user->suspended_at !== null) {
            return 'تم إيقاف حسابك مؤقتًا. تواصل مع المسؤول.';
        }

        if ($user->joinRequest?->status === JoinRequest::STATUS_REJECTED) {
            return 'تم رفض طلب انضمامك. تواصل مع المسؤول.';
        }

        return 'حسابك غير مفعل بعد. تواصل مع المسؤول.';
    }
}
