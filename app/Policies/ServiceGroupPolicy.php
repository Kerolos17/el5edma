<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ServiceGroup;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServiceGroupPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            UserRole::SuperAdmin,
            UserRole::ServiceLeader,
            UserRole::FamilyLeader,
        ], true);
    }

    public function view(User $user, ServiceGroup $serviceGroup): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        if ($user->role === UserRole::ServiceLeader) {
            return $user->managesServiceGroup($serviceGroup->id);
        }

        return $user->role === UserRole::FamilyLeader
            && $user->service_group_id === $serviceGroup->id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::ServiceLeader], true);
    }

    public function update(User $user, ServiceGroup $serviceGroup): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        if ($user->role === UserRole::ServiceLeader) {
            return $user->managesServiceGroup($serviceGroup->id);
        }

        return $user->role === UserRole::FamilyLeader
            && $user->service_group_id === $serviceGroup->id;
    }

    public function delete(User $user, ServiceGroup $serviceGroup): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    public function restore(User $user, ServiceGroup $serviceGroup): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    public function forceDelete(User $user, ServiceGroup $serviceGroup): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    public function manageRegistrationLink(User $user, ServiceGroup $serviceGroup): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        if ($user->role === UserRole::ServiceLeader) {
            return $user->managesServiceGroup($serviceGroup->id);
        }

        return $user->role === UserRole::FamilyLeader
            && $user->service_group_id === $serviceGroup->id;
    }
}
