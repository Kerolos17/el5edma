<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\JoinRequest;
use App\Models\User;

class JoinRequestPolicy
{
    /**
     * Viewing the queue: back-office roles only, scoped to their own span of
     * control — SuperAdmin sees everything, ServiceLeader their managed
     * groups, FamilyLeader only requests targeting their own group.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            UserRole::SuperAdmin,
            UserRole::ServiceLeader,
            UserRole::FamilyLeader,
        ], true);
    }

    public function view(User $user, JoinRequest $joinRequest): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        if ($user->role === UserRole::ServiceLeader) {
            return $user->managesServiceGroup($joinRequest->service_group_id);
        }

        if ($user->role === UserRole::FamilyLeader) {
            return $joinRequest->service_group_id !== null
                && $user->service_group_id === $joinRequest->service_group_id;
        }

        return false;
    }

    /**
     * Whether this reviewer may take ANY decision on this request. The role
     * the reviewer may GRANT is checked separately in canAssign — a family
     * leader reviews their own group's requests but can only ever approve a
     * plain servant.
     */
    public function review(User $user, JoinRequest $joinRequest): bool
    {
        return $this->view($user, $joinRequest)
            && in_array($user->role, [UserRole::SuperAdmin, UserRole::ServiceLeader, UserRole::FamilyLeader], true);
    }

    /**
     * Which roles this reviewer may grant on approval. Mirrors
     * UserPolicy::assignRole / manageServiceGroup semantics so no reviewer
     * can elevate anyone above their own span of control.
     *
     * @return list<string>
     */
    public function assignableRoles(User $user): array
    {
        return match ($user->role) {
            UserRole::SuperAdmin => [
                UserRole::Servant->value,
                UserRole::FamilyLeader->value,
                UserRole::ServiceLeader->value,
            ],
            UserRole::ServiceLeader => [
                UserRole::Servant->value,
                UserRole::FamilyLeader->value,
            ],
            UserRole::FamilyLeader => [
                UserRole::Servant->value,
            ],
            default => [],
        };
    }

    /**
     * Suspending / reactivating an approved account follows the same span of
     * control as reviewing its request.
     */
    public function suspend(User $user, JoinRequest $joinRequest): bool
    {
        return $this->review($user, $joinRequest)
            && $joinRequest->status === JoinRequest::STATUS_APPROVED;
    }
}
