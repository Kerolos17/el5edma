<?php

namespace App\Policies;

use App\Models\PrayerRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PrayerRequestPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any prayer requests.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view prayer requests (scoped by service group)
        return in_array($user->role, ['super_admin', 'service_leader', 'family_leader', 'servant']);
    }

    /**
     * Determine whether the user can view the prayer request.
     */
    public function view(User $user, PrayerRequest $prayerRequest): bool
    {
        // Super admin and service leader have full access
        if (in_array($user->role, ['super_admin', 'service_leader'])) {
            return true;
        }

        // Family leaders can view prayer requests for beneficiaries in their service group
        if ($user->role === 'family_leader') {
            return $user->service_group_id === $prayerRequest->beneficiary->service_group_id;
        }

        // Servants can view prayer requests they created
        if ($user->role === 'servant') {
            return $prayerRequest->created_by === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create prayer requests.
     */
    public function create(User $user): bool
    {
        // All roles can create prayer requests
        return in_array($user->role, ['super_admin', 'service_leader', 'family_leader', 'servant']);
    }

    /**
     * Determine whether the user can update the prayer request.
     */
    public function update(User $user, PrayerRequest $prayerRequest): bool
    {
        // Super admin and service leader have full access
        if (in_array($user->role, ['super_admin', 'service_leader'])) {
            return true;
        }

        // Family leaders can update prayer requests for beneficiaries in their service group
        if ($user->role === 'family_leader') {
            return $user->service_group_id === $prayerRequest->beneficiary->service_group_id;
        }

        // Servants cannot update prayer requests (read-only after creation)
        return false;
    }

    /**
     * Determine whether the user can delete the prayer request.
     */
    public function delete(User $user, PrayerRequest $prayerRequest): bool
    {
        // Only super_admin, service_leader, and family_leader can delete prayer requests
        // Servants cannot delete prayer requests
        return in_array($user->role, ['super_admin', 'service_leader', 'family_leader']);
    }

    /**
     * Determine whether the user can restore the prayer request.
     */
    public function restore(User $user, PrayerRequest $prayerRequest): bool
    {
        // Only super_admin and service_leader can restore prayer requests
        return in_array($user->role, ['super_admin', 'service_leader']);
    }

    /**
     * Determine whether the user can permanently delete the prayer request.
     */
    public function forceDelete(User $user, PrayerRequest $prayerRequest): bool
    {
        // Only super_admin can permanently delete prayer requests
        return $user->role === 'super_admin';
    }
}
