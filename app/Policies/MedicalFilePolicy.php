<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\MedicalFile;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MedicalFilePolicy
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

    public function view(User $user, MedicalFile $medicalFile): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        $medicalFile->loadMissing('beneficiary');

        if ($medicalFile->beneficiary === null) {
            return false;
        }

        if ($user->role === UserRole::ServiceLeader) {
            return $user->managesServiceGroup($medicalFile->beneficiary->service_group_id);
        }

        if ($user->role === UserRole::FamilyLeader) {
            return $user->service_group_id === $medicalFile->beneficiary->service_group_id;
        }

        if ($user->role === UserRole::Servant) {
            return $medicalFile->beneficiary->assigned_servant_id === $user->id
                || (
                    $user->service_group_id !== null
                    && $user->service_group_id === $medicalFile->beneficiary->service_group_id
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

    public function update(User $user, MedicalFile $medicalFile): bool
    {
        return false;
    }

    public function delete(User $user, MedicalFile $medicalFile): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        $medicalFile->loadMissing('beneficiary');

        if ($medicalFile->beneficiary === null) {
            return false;
        }

        if ($user->role === UserRole::ServiceLeader) {
            return $user->managesServiceGroup($medicalFile->beneficiary->service_group_id);
        }

        return $user->role             === UserRole::FamilyLeader
            && $user->service_group_id === $medicalFile->beneficiary->service_group_id;
    }

    public function restore(User $user, MedicalFile $medicalFile): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        $medicalFile->loadMissing('beneficiary');

        return $user->role === UserRole::ServiceLeader
            && $medicalFile->beneficiary !== null
            && $user->managesServiceGroup($medicalFile->beneficiary->service_group_id);
    }

    public function forceDelete(User $user, MedicalFile $medicalFile): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }
}
