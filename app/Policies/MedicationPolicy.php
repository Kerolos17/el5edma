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
        return in_array($user->role, [
            UserRole::SuperAdmin,
            UserRole::ServiceLeader,
            UserRole::FamilyLeader,
            UserRole::Servant,
        ], true);
    }

    public function view(User $user, Medication $medication): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        $medication->loadMissing('beneficiary');

        if ($medication->beneficiary === null) {
            return false;
        }

        if ($user->role === UserRole::ServiceLeader) {
            return $user->managesServiceGroup($medication->beneficiary->service_group_id);
        }

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
        return $user->role === UserRole::SuperAdmin
            || ($user->role === UserRole::ServiceLeader && $user->managedServiceGroups()->isNotEmpty())
            || $user->role === UserRole::FamilyLeader;
    }

    public function update(User $user, Medication $medication): bool
    {
        return $this->canManage($user, $medication);
    }

    public function delete(User $user, Medication $medication): bool
    {
        return $this->canManage($user, $medication);
    }

    public function restore(User $user, Medication $medication): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        $medication->loadMissing('beneficiary');

        return $user->role === UserRole::ServiceLeader
            && $medication->beneficiary !== null
            && $user->managesServiceGroup($medication->beneficiary->service_group_id);
    }

    public function forceDelete(User $user, Medication $medication): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    private function canManage(User $user, Medication $medication): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        $medication->loadMissing('beneficiary');

        if ($medication->beneficiary === null) {
            return false;
        }

        if ($user->role === UserRole::ServiceLeader) {
            return $user->managesServiceGroup($medication->beneficiary->service_group_id);
        }

        return $user->role === UserRole::FamilyLeader
            && $user->service_group_id === $medication->beneficiary->service_group_id;
    }
}
