<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AuditLogPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any audit logs.
     */
    public function viewAny(User $user): bool
    {
        // Only super_admin and service_leader can view audit logs
        return $user->role->isAdminLevel();
    }

    /**
     * Determine whether the user can view the audit log.
     */
    public function view(User $user, AuditLog $auditLog): bool
    {
        // Only super_admin and service_leader can view audit logs
        return $user->role->isAdminLevel();
    }

    /**
     * Determine whether the user can create audit logs.
     */
    public function create(User $user): bool
    {
        // Audit logs are created automatically by observers - no manual creation
        return false;
    }

    /**
     * Determine whether the user can update the audit log.
     */
    public function update(User $user, AuditLog $auditLog): bool
    {
        // Audit logs are immutable - no one can edit them
        return false;
    }

    /**
     * Determine whether the user can delete the audit log.
     */
    public function delete(User $user, AuditLog $auditLog): bool
    {
        // Audit logs should not be deleted - they are permanent records
        return false;
    }

    /**
     * Determine whether the user can restore the audit log.
     */
    public function restore(User $user, AuditLog $auditLog): bool
    {
        // Audit logs should not be deleted, so no need to restore
        return false;
    }

    /**
     * Determine whether the user can permanently delete the audit log.
     */
    public function forceDelete(User $user, AuditLog $auditLog): bool
    {
        // Only super_admin can permanently delete audit logs (for cleanup purposes)
        return $user->role === UserRole::SuperAdmin;
    }
}
