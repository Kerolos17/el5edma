<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        // Servants cannot access users at all
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::ServiceLeader, UserRole::FamilyLeader]);
    }

    /**
     * Determine whether the user can view the user.
     */
    public function view(User $user, User $model): bool
    {
        // Users can always view their own profile
        if ($user->id === $model->id) {
            return true;
        }

        // Super admin and service leader can view all users
        if ($user->role->isAdminLevel()) {
            return true;
        }

        // Family leaders can view users in their service group
        if ($user->role === UserRole::FamilyLeader) {
            return $user->service_group_id === $model->service_group_id;
        }

        // Servants can view users in their service group
        if ($user->role === UserRole::Servant) {
            return $user->service_group_id === $model->service_group_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        // Only super_admin and service_leader can create users
        // Family leaders and servants cannot create users
        return $user->role->isAdminLevel();
    }

    /**
     * Determine whether the user can update the user.
     */
    public function update(User $user, User $model): bool
    {
        // Users can always update their own profile (limited fields)
        if ($user->id === $model->id) {
            return true;
        }

        // Super admin and service leader can update all users
        if ($user->role->isAdminLevel()) {
            return true;
        }

        // Family leaders can update users in their service group (limited updates)
        if ($user->role === UserRole::FamilyLeader) {
            return $user->service_group_id === $model->service_group_id;
        }

        // Servants cannot update other users
        return false;
    }

    /**
     * Determine whether the user can delete the user.
     */
    public function delete(User $user, User $model): bool
    {
        // Users cannot delete themselves
        if ($user->id === $model->id) {
            return false;
        }

        // Only super_admin and service_leader can delete users
        // Family leaders and servants cannot delete users
        return $user->role->isAdminLevel();
    }

    /**
     * Determine whether the user can restore the user.
     */
    public function restore(User $user, User $model): bool
    {
        // Only super_admin and service_leader can restore users
        return $user->role->isAdminLevel();
    }

    /**
     * Determine whether the user can permanently delete the user.
     */
    public function forceDelete(User $user, User $model): bool
    {
        // Users cannot permanently delete themselves
        if ($user->id === $model->id) {
            return false;
        }

        // Only super_admin can permanently delete users
        return $user->role === UserRole::SuperAdmin;
    }

    /**
     * Determine whether the user can assign roles to users.
     */
    public function assignRole(User $user, User $model): bool
    {
        // Users cannot assign roles to themselves
        if ($user->id === $model->id) {
            return false;
        }

        // Only super_admin can assign roles
        // Service leaders cannot assign roles to prevent privilege escalation
        return $user->role === UserRole::SuperAdmin;
    }

    /**
     * Determine whether the user can manage service group assignments.
     */
    public function manageServiceGroup(User $user, User $model): bool
    {
        // Users cannot change their own service group
        if ($user->id === $model->id) {
            return false;
        }

        // Only super_admin and service_leader can manage service group assignments
        return $user->role->isAdminLevel();
    }
}
