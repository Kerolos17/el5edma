<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Medication;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MedicationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::ServiceLeader, UserRole::FamilyLeader, UserRole::Servant]);
    }

    public function view(User $user, Medication $medication): bool
    {
        if ($user->role->isAdminLevel()) {
            return true;
        }

        $medication->loadMissing('beneficiary');

        if ($user->role === UserRole::FamilyLeader) {
            return $user->service_group_id === $medication->beneficiary->service_group_id;
        }

        if ($user->role === UserRole::Servant) {
            return $medication->beneficiary->assigned_servant_id === $user->id
                || (
                    $user->service_group_id !== null
                    && $user->service_group_id === $medication->beneficiary->service_group_id
                );
        }

        return false;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::ServiceLeader, UserRole::FamilyLeader]);
    }

    public function update(User $user, Medication $medication): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::ServiceLeader, UserRole::FamilyLeader]);
    }

    public function delete(User $user, Medication $medication): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::ServiceLeader, UserRole::FamilyLeader]);
    }

    public function restore(User $user, Medication $medication): bool
    {
        return $user->role->isAdminLevel();
    }

    public function forceDelete(User $user, Medication $medication): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }
}
