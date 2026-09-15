<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Auth\Access\HandlesAuthorization;

class VisitPolicy
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

    public function view(User $user, Visit $visit): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        $visit->loadMissing('beneficiary');

        if ($visit->beneficiary === null) {
            return false;
        }

        if ($user->role === UserRole::ServiceLeader) {
            return $user->managesServiceGroup($visit->beneficiary->service_group_id);
        }

        if ($user->role === UserRole::FamilyLeader) {
            return $user->service_group_id === $visit->beneficiary->service_group_id;
        }

        if ($user->role === UserRole::Servant) {
            return $this->servantCanViewVisit($user, $visit);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::SuperAdmin
            || ($user->role === UserRole::ServiceLeader && $user->managedServiceGroups()->isNotEmpty())
            || in_array($user->role, [UserRole::FamilyLeader, UserRole::Servant], true);
    }

    public function update(User $user, Visit $visit): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        $visit->loadMissing('beneficiary');

        if ($visit->beneficiary === null) {
            return false;
        }

        if ($user->role === UserRole::ServiceLeader) {
            return $user->managesServiceGroup($visit->beneficiary->service_group_id);
        }

        if ($user->role === UserRole::FamilyLeader) {
            return $user->service_group_id === $visit->beneficiary->service_group_id;
        }

        if ($user->role === UserRole::Servant) {
            return $this->servantCanUpdateVisit($user, $visit);
        }

        return false;
    }

    public function delete(User $user, Visit $visit): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        if ($user->role !== UserRole::ServiceLeader) {
            return false;
        }

        $visit->loadMissing('beneficiary');

        return $visit->beneficiary !== null
            && $user->managesServiceGroup($visit->beneficiary->service_group_id);
    }

    public function restore(User $user, Visit $visit): bool
    {
        return $this->delete($user, $visit);
    }

    public function forceDelete(User $user, Visit $visit): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

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
}
