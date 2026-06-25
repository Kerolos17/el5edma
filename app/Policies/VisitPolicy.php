<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Auth\Access\HandlesAuthorization;

class VisitPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any visits.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view visits (scoped by service group)
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::ServiceLeader, UserRole::FamilyLeader, UserRole::Servant]);
    }

    /**
     * Determine whether the user can view the visit.
     */
    public function view(User $user, Visit $visit): bool
    {
        if ($user->role->isAdminLevel()) {
            return true;
        }

        $visit->loadMissing('beneficiary');

        if ($visit->beneficiary === null) {
            return false;
        }

        if ($user->role === UserRole::FamilyLeader) {
            return $user->service_group_id === $visit->beneficiary->service_group_id;
        }

        if ($user->role === UserRole::Servant) {
            return $this->servantCanViewVisit($user, $visit);
        }

        return false;
    }

    /**
     * Determine whether the user can create visits.
     */
    public function create(User $user): bool
    {
        // All roles can create visits (servants can create visits for their beneficiaries)
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::ServiceLeader, UserRole::FamilyLeader, UserRole::Servant]);
    }

    /**
     * Determine whether the user can update the visit.
     */
    public function update(User $user, Visit $visit): bool
    {
        // Super admin and service leader have full access
        if ($user->role->isAdminLevel()) {
            return true;
        }

        $visit->loadMissing('beneficiary');

        if ($user->role === UserRole::FamilyLeader) {
            return $visit->beneficiary !== null
                && $user->service_group_id === $visit->beneficiary->service_group_id;
        }

        // Servants can update visits they recorded or participated in.
        if ($user->role === UserRole::Servant) {
            return $this->servantCanUpdateVisit($user, $visit);
        }

        return false;
    }

    /**
     * Determine whether a servant can view a visit.
     *
     * Servants may view any visit for beneficiaries in their service group.
     */
    private function servantCanViewVisit(User $user, Visit $visit): bool
    {
        if ($visit->beneficiary === null) {
            return false;
        }

        if ($user->service_group_id === null) {
            return $visit->created_by === $user->id;
        }

        return $user->service_group_id === $visit->beneficiary->service_group_id;
    }

    /**
     * Determine whether a servant can update a visit.
     *
     * Servants may update visits they created or where they are listed as a participant,
     * provided the visit belongs to a beneficiary in their service group.
     */
    private function servantCanUpdateVisit(User $user, Visit $visit): bool
    {
        if (! $this->servantCanViewVisit($user, $visit)) {
            return false;
        }

        if ($visit->created_by === $user->id) {
            return true;
        }

        $visit->loadMissing('servants');

        return $visit->servants->contains('id', $user->id);
    }

    /**
     * Determine whether the user can delete the visit.
     */
    public function delete(User $user, Visit $visit): bool
    {
        // Only super_admin and service_leader can delete visits
        // Family leaders and servants cannot delete
        return $user->role->isAdminLevel();
    }

    /**
     * Determine whether the user can restore the visit.
     */
    public function restore(User $user, Visit $visit): bool
    {
        // Only super_admin and service_leader can restore visits
        return $user->role->isAdminLevel();
    }

    /**
     * Determine whether the user can permanently delete the visit.
     */
    public function forceDelete(User $user, Visit $visit): bool
    {
        // Only super_admin can permanently delete visits
        return $user->role === UserRole::SuperAdmin;
    }
}
