<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureServantAccess
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
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('filament.admin.auth.login')
                ->with('error', 'حسابك غير مفعّل بعد. تواصل مع المسؤول.');
        }

        if (! in_array($user->role, self::ALLOWED_ROLES, true)) {
            abort(403, 'غير مصرح بالوصول إلى هذه الصفحة.');
        }

        return $next($request);
    }
}
