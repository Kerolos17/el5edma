<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BeneficiaryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            UserRole::SuperAdmin,
            UserRole::ServiceLeader,
            UserRole::FamilyLeader,
            UserRole::Servant,
        ], true);
    }

    public function view(User $user, Beneficiary $beneficiary): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        if ($user->role === UserRole::ServiceLeader) {
            return $user->managesServiceGroup($beneficiary->service_group_id);
        }

        if ($user->role === UserRole::FamilyLeader) {
            return $user->service_group_id === $beneficiary->service_group_id;
        }

        if ($user->role === UserRole::Servant) {
            return $beneficiary->assigned_servant_id === $user->id
                || (
                    $user->service_group_id !== null
                    && $user->service_group_id === $beneficiary->service_group_id
                );
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::SuperAdmin
            || ($user->role === UserRole::ServiceLeader && $user->managedServiceGroups()->isNotEmpty())
            || $user->role === UserRole::FamilyLeader;
    }

    public function update(User $user, Beneficiary $beneficiary): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        if ($user->role === UserRole::ServiceLeader) {
            return $user->managesServiceGroup($beneficiary->service_group_id);
        }

        return $user->role             === UserRole::FamilyLeader
            && $user->service_group_id === $beneficiary->service_group_id;
    }

    public function delete(User $user, Beneficiary $beneficiary): bool
    {
        return $this->update($user, $beneficiary);
    }

    public function restore(User $user, Beneficiary $beneficiary): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        return $user->role === UserRole::ServiceLeader
            && $user->managesServiceGroup($beneficiary->service_group_id);
    }

    public function forceDelete(User $user, Beneficiary $beneficiary): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }
}
