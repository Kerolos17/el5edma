<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

class PermissionHelper
{
    /**
     * Check if current user can create/edit/delete (not a servant)
     */
    public static function canModify(): bool
    {
        $user = Auth::user();

        return in_array($user?->role, ['super_admin', 'service_leader', 'family_leader']);
    }

    /**
     * Check if current user is a servant (read-only)
     */
    public static function isServant(): bool
    {
        return Auth::user()?->role === 'servant';
    }

    /**
     * Check if current user is super admin
     */
    public static function isSuperAdmin(): bool
    {
        return Auth::user()?->role === 'super_admin';
    }

    /**
     * Get allowed roles for modification
     */
    public static function modifyRoles(): array
    {
        return ['super_admin', 'service_leader', 'family_leader'];
    }
}
