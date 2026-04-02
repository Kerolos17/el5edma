<?php

namespace Tests\Traits;

use App\Enums\UserRole;
use App\Models\ServiceGroup;
use App\Models\User;

trait CreatesTestUsers
{
    protected function createSuperAdmin(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role'      => UserRole::SuperAdmin,
            'is_active' => true,
        ], $overrides));
    }

    protected function createServiceLeader(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role'      => UserRole::ServiceLeader,
            'is_active' => true,
        ], $overrides));
    }

    protected function createFamilyLeader(ServiceGroup $serviceGroup, array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role'             => UserRole::FamilyLeader,
            'service_group_id' => $serviceGroup->id,
            'is_active'        => true,
        ], $overrides));
    }

    protected function createServant(ServiceGroup $serviceGroup, array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role'             => UserRole::Servant,
            'service_group_id' => $serviceGroup->id,
            'is_active'        => true,
        ], $overrides));
    }

    protected function createServiceGroupWithLeader(): array
    {
        $group  = ServiceGroup::factory()->create();
        $leader = $this->createFamilyLeader($group);
        $group->update(['leader_id' => $leader->id]);

        return [$group, $leader];
    }
}
