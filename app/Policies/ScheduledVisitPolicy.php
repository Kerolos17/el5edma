<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ScheduledVisit;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ScheduledVisitPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any scheduled visits.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view scheduled visits (scoped by service group)
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::ServiceLeader, UserRole::FamilyLeader, UserRole::Servant]);
    }

    /**
     * Determine whether the user can view the scheduled visit.
     */
    public function view(User $user, ScheduledVisit $scheduledVisit): bool
    {
        // Super admin and service leader have full access
        if ($user->role->isAdminLevel()) {
            return true;
        }

        // Family leaders can view scheduled visits for beneficiaries in their service group
        if ($user->role === UserRole::FamilyLeader) {
            return $user->service_group_id === $scheduledVisit->beneficiary->service_group_id;
        }

        // Servants can view scheduled visits assigned to them
        if ($user->role === UserRole::Servant) {
            return $scheduledVisit->assigned_servant_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create scheduled visits.
     */
    public function create(User $user): bool
    {
        // Only super_admin, service_leader, and family_leader can create scheduled visits
        // Servants cannot create scheduled visits
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::ServiceLeader, UserRole::FamilyLeader]);
    }

    /**
     * Determine whether the user can update the scheduled visit.
     */
    public function update(User $user, ScheduledVisit $scheduledVisit): bool
    {
        // Super admin and service leader have full access
        if ($user->role->isAdminLevel()) {
            return true;
        }

        // Family leaders can update scheduled visits for beneficiaries in their service group
        if ($user->role === UserRole::FamilyLeader) {
            return $user->service_group_id === $scheduledVisit->beneficiary->service_group_id;
        }

        // Servants cannot update scheduled visits
        return false;
    }

    /**
     * Determine whether the user can delete the scheduled visit.
     */
    public function delete(User $user, ScheduledVisit $scheduledVisit): bool
    {
        // Only super_admin, service_leader, and family_leader can delete scheduled visits
        // Servants cannot delete scheduled visits
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::ServiceLeader, UserRole::FamilyLeader]);
    }

    /**
     * Determine whether the user can restore the scheduled visit.
     */
    public function restore(User $user, ScheduledVisit $scheduledVisit): bool
    {
        // Only super_admin and service_leader can restore scheduled visits
        return $user->role->isAdminLevel();
    }

    /**
     * Determine whether the user can permanently delete the scheduled visit.
     */
    public function forceDelete(User $user, ScheduledVisit $scheduledVisit): bool
    {
        // Only super_admin can permanently delete scheduled visits
        return $user->role === UserRole::SuperAdmin;
    }
}
