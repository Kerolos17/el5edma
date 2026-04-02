<?php

namespace App\Helpers;

use App\Enums\UserRole;
use Illuminate\Support\Facades\Auth;

class PermissionHelper
{
    /**
     * Check if current user can create/edit/delete (not a servant)
     */
    public static function canModify(): bool
    {
        return Auth::user()?->role?->isAdminLevel() ?? false;
    }

    /**
     * Check if current user is a servant (read-only)
     */
    public static function isServant(): bool
    {
        return Auth::user()?->role === UserRole::Servant;
    }

    /**
     * Check if current user is super admin
     */
    public static function isSuperAdmin(): bool
    {
        return Auth::user()?->role === UserRole::SuperAdmin;
    }
}
