<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\PrayerRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PrayerRequestPolicy
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

    public function view(User $user, PrayerRequest $prayerRequest): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        $prayerRequest->loadMissing('beneficiary');

        if ($prayerRequest->beneficiary === null) {
            return false;
        }

        if ($user->role === UserRole::ServiceLeader) {
            return $user->managesServiceGroup($prayerRequest->beneficiary->service_group_id);
        }

        if ($user->role === UserRole::FamilyLeader) {
            return $user->service_group_id === $prayerRequest->beneficiary->service_group_id;
        }

        if ($user->role === UserRole::Servant) {
            return $prayerRequest->created_by === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::SuperAdmin
            || ($user->role === UserRole::ServiceLeader && $user->managedServiceGroups()->isNotEmpty())
            || in_array($user->role, [UserRole::FamilyLeader, UserRole::Servant], true);
    }

    public function update(User $user, PrayerRequest $prayerRequest): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        $prayerRequest->loadMissing('beneficiary');

        if ($prayerRequest->beneficiary === null) {
            return false;
        }

        if ($user->role === UserRole::ServiceLeader) {
            return $user->managesServiceGroup($prayerRequest->beneficiary->service_group_id);
        }

        return $user->role             === UserRole::FamilyLeader
            && $user->service_group_id === $prayerRequest->beneficiary->service_group_id;
    }

    public function delete(User $user, PrayerRequest $prayerRequest): bool
    {
        return $this->update($user, $prayerRequest);
    }

    public function restore(User $user, PrayerRequest $prayerRequest): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        $prayerRequest->loadMissing('beneficiary');

        return $user->role === UserRole::ServiceLeader
            && $prayerRequest->beneficiary !== null
            && $user->managesServiceGroup($prayerRequest->beneficiary->service_group_id);
    }

    public function forceDelete(User $user, PrayerRequest $prayerRequest): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }
}
