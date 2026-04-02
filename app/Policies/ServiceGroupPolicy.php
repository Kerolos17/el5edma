<?php
namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ServiceGroup;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServiceGroupPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any service groups.
     */
    public function viewAny(User $user): bool
    {
        // Servants cannot access service groups at all
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::ServiceLeader, UserRole::FamilyLeader]);
    }

    /**
     * Determine whether the user can view the service group.
     */
    public function view(User $user, ServiceGroup $serviceGroup): bool
    {
        // Super admin and service leader can view all service groups
        if ($user->role->isAdminLevel()) {
            return true;
        }

        // Family leaders and servants can only view their own service group
        return $user->service_group_id === $serviceGroup->id;
    }

    /**
     * Determine whether the user can create service groups.
     */
    public function create(User $user): bool
    {
        // Only super_admin and service_leader can create service groups
        // Family leaders and servants cannot create service groups
        return $user->role->isAdminLevel();
    }

    /**
     * Determine whether the user can update the service group.
     */
    public function update(User $user, ServiceGroup $serviceGroup): bool
    {
        // Super admin and service leader can update all service groups
        if ($user->role->isAdminLevel()) {
            return true;
        }

        // Family leaders can only update their own service group (limited updates)
        if ($user->role === UserRole::FamilyLeader) {
            return $user->service_group_id === $serviceGroup->id;
        }

        // Servants cannot update service groups
        return false;
    }

    /**
     * Determine whether the user can delete the service group.
     */
    public function delete(User $user, ServiceGroup $serviceGroup): bool
    {
        // Only super_admin can delete service groups
        // Service leaders, family leaders, and servants cannot delete
        return $user->role === UserRole::SuperAdmin;
    }

    /**
     * Determine whether the user can restore the service group.
     */
    public function restore(User $user, ServiceGroup $serviceGroup): bool
    {
        // Only super_admin can restore service groups
        return $user->role === UserRole::SuperAdmin;
    }

    /**
     * Determine whether the user can permanently delete the service group.
     */
    public function forceDelete(User $user, ServiceGroup $serviceGroup): bool
    {
        // Only super_admin can permanently delete service groups
        return $user->role === UserRole::SuperAdmin;
    }

    /**
     * Determine whether the user can manage registration links for the service group.
     * Requirements: 7.6
     */
    public function manageRegistrationLink(User $user, ServiceGroup $serviceGroup): bool
    {
        // Super admin and service leader can manage all groups
        if ($user->role->isAdminLevel()) {
            return true;
        }

        // Family leader can only manage their own group
        if ($user->role === UserRole::FamilyLeader) {
            return $user->service_group_id === $serviceGroup->id;
        }

        // Servants cannot manage registration links
        return false;
    }
}
