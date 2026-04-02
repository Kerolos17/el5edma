<?php

namespace App\Policies;

use App\Models\MedicalFile;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MedicalFilePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any medical files.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view medical files (scoped by service group)
        return in_array($user->role, ['super_admin', 'service_leader', 'family_leader', 'servant']);
    }

    /**
     * Determine whether the user can view the medical file.
     */
    public function view(User $user, MedicalFile $medicalFile): bool
    {
        // Super admin and service leader have full access
        if (in_array($user->role, ['super_admin', 'service_leader'])) {
            return true;
        }

        // Family leaders can view medical files for beneficiaries in their service group
        if ($user->role === 'family_leader') {
            return $user->service_group_id === $medicalFile->beneficiary->service_group_id;
        }

        // Servants can view medical files for their assigned beneficiaries
        if ($user->role === 'servant') {
            return $medicalFile->beneficiary->assigned_servant_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create medical files.
     */
    public function create(User $user): bool
    {
        // Only super_admin, service_leader, and family_leader can create medical files
        // Servants cannot create medical files
        return in_array($user->role, ['super_admin', 'service_leader', 'family_leader']);
    }

    /**
     * Determine whether the user can update the medical file.
     */
    public function update(User $user, MedicalFile $medicalFile): bool
    {
        // Medical files are immutable - no one can edit them
        return false;
    }

    /**
     * Determine whether the user can delete the medical file.
     */
    public function delete(User $user, MedicalFile $medicalFile): bool
    {
        // Only super_admin, service_leader, and family_leader can delete medical files
        // Servants cannot delete medical files
        return in_array($user->role, ['super_admin', 'service_leader', 'family_leader']);
    }

    /**
     * Determine whether the user can restore the medical file.
     */
    public function restore(User $user, MedicalFile $medicalFile): bool
    {
        // Only super_admin and service_leader can restore medical files
        return in_array($user->role, ['super_admin', 'service_leader']);
    }

    /**
     * Determine whether the user can permanently delete the medical file.
     */
    public function forceDelete(User $user, MedicalFile $medicalFile): bool
    {
        // Only super_admin can permanently delete medical files
        return $user->role === 'super_admin';
    }
}
