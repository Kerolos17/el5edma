<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BeneficiaryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any beneficiaries.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view beneficiaries (scoped by service group)
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::ServiceLeader, UserRole::FamilyLeader, UserRole::Servant]);
    }

    /**
     * Determine whether the user can view the beneficiary.
     */
    public function view(User $user, Beneficiary $beneficiary): bool
    {
        // Super admin and service leader have full access
        if ($user->role->isAdminLevel()) {
            return true;
        }

        // Family leaders and servants can only view beneficiaries in their service group
        return $user->service_group_id === $beneficiary->service_group_id;
    }

    /**
     * Determine whether the user can create beneficiaries.
     */
    public function create(User $user): bool
    {
        // Only super_admin, service_leader, and family_leader can create
        // Servants cannot create beneficiaries
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::ServiceLeader, UserRole::FamilyLeader]);
    }

    /**
     * Determine whether the user can update the beneficiary.
     */
    public function update(User $user, Beneficiary $beneficiary): bool
    {
        // Super admin and service leader have full access
        if ($user->role->isAdminLevel()) {
            return true;
        }

        // Family leaders can update beneficiaries in their service group
        if ($user->role === UserRole::FamilyLeader) {
            return $user->service_group_id === $beneficiary->service_group_id;
        }

        // Servants cannot update beneficiaries
        return false;
    }

    /**
     * Determine whether the user can delete the beneficiary.
     */
    public function delete(User $user, Beneficiary $beneficiary): bool
    {
        // Super admin and service leader can delete any beneficiaries
        if ($user->role->isAdminLevel()) {
            return true;
        }

        // Family leaders can delete beneficiaries in their service group
        if ($user->role === UserRole::FamilyLeader) {
            return $user->service_group_id === $beneficiary->service_group_id;
        }

        // Servants cannot delete beneficiaries
        return false;
    }

    /**
     * Determine whether the user can restore the beneficiary.
     */
    public function restore(User $user, Beneficiary $beneficiary): bool
    {
        // Only super_admin and service_leader can restore beneficiaries
        return $user->role->isAdminLevel();
    }

    /**
     * Determine whether the user can permanently delete the beneficiary.
     */
    public function forceDelete(User $user, Beneficiary $beneficiary): bool
    {
        // Only super_admin can permanently delete beneficiaries
        return $user->role === UserRole::SuperAdmin;
    }
}
