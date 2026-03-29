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
        $user = Auth::user();
        return in_array($user?->role, [UserRole::SuperAdmin, UserRole::ServiceLeader, UserRole::FamilyLeader]);
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

    /**
     * Get allowed roles for modification
     */
    public static function modifyRoles(): array
    {
        return [UserRole::SuperAdmin, UserRole::ServiceLeader, UserRole::FamilyLeader];
    }
}
