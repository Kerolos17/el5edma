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
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        $scheduledVisit->loadMissing('beneficiary');

        if ($scheduledVisit->beneficiary === null) {
            return false;
        }

        if ($user->role === UserRole::ServiceLeader) {
            return $user->managesServiceGroup($scheduledVisit->beneficiary->service_group_id);
        }

        if ($user->role === UserRole::FamilyLeader) {
            return $user->service_group_id === $scheduledVisit->beneficiary->service_group_id;
        }

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
        return $user->role === UserRole::SuperAdmin
            || ($user->role === UserRole::ServiceLeader && ! empty($user->managedServiceGroupIds()))
            || $user->role === UserRole::FamilyLeader;
    }

    /**
     * Determine whether the user can update the scheduled visit.
     */
    public function update(User $user, ScheduledVisit $scheduledVisit): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        $scheduledVisit->loadMissing('beneficiary');

        if ($scheduledVisit->beneficiary === null) {
            return false;
        }

        if ($user->role === UserRole::ServiceLeader) {
            return $user->managesServiceGroup($scheduledVisit->beneficiary->service_group_id);
        }

        if ($user->role === UserRole::FamilyLeader) {
            return $user->service_group_id === $scheduledVisit->beneficiary->service_group_id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the scheduled visit.
     */
    public function delete(User $user, ScheduledVisit $scheduledVisit): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        $scheduledVisit->loadMissing('beneficiary');

        if ($scheduledVisit->beneficiary === null) {
            return false;
        }

        if ($user->role === UserRole::ServiceLeader) {
            return $user->managesServiceGroup($scheduledVisit->beneficiary->service_group_id);
        }

        if ($user->role === UserRole::FamilyLeader) {
            return $user->service_group_id === $scheduledVisit->beneficiary->service_group_id;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the scheduled visit.
     */
    public function restore(User $user, ScheduledVisit $scheduledVisit): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        if ($user->role !== UserRole::ServiceLeader) {
            return false;
        }

        $scheduledVisit->loadMissing('beneficiary');

        return $scheduledVisit->beneficiary !== null
            && $user->managesServiceGroup($scheduledVisit->beneficiary->service_group_id);
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
