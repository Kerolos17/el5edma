<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\UserResource;
use App\Models\ServiceGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class UserResourceScopeTest extends TestCase
{
    use CreatesTestUsers;
    use RefreshDatabase;

    public function test_service_leader_user_query_is_scoped_to_managed_groups_plus_self(): void
    {
        $groupA        = ServiceGroup::factory()->create();
        $groupB        = ServiceGroup::factory()->create();
        $serviceLeader = $this->createServiceLeader();
        $groupA->update(['service_leader_id' => $serviceLeader->id]);

        $inScopeServant      = $this->createServant($groupA);
        $inScopeFamilyLeader = $this->createFamilyLeader($groupA);
        $outScopeServant     = $this->createServant($groupB);

        $this->actingAs($serviceLeader);

        $ids = UserResource::getEloquentQuery()->pluck('id');

        $this->assertContains($serviceLeader->id, $ids);
        $this->assertContains($inScopeServant->id, $ids);
        $this->assertContains($inScopeFamilyLeader->id, $ids);
        $this->assertNotContains($outScopeServant->id, $ids);
    }

    public function test_family_leader_user_query_is_scoped_to_group_plus_self(): void
    {
        $groupA          = ServiceGroup::factory()->create();
        $groupB          = ServiceGroup::factory()->create();
        $familyLeader    = $this->createFamilyLeader($groupA);
        $inScopeServant  = $this->createServant($groupA);
        $outScopeServant = $this->createServant($groupB);

        $this->actingAs($familyLeader);

        $ids = UserResource::getEloquentQuery()->pluck('id');

        $this->assertContains($familyLeader->id, $ids);
        $this->assertContains($inScopeServant->id, $ids);
        $this->assertNotContains($outScopeServant->id, $ids);
    }

    public function test_servant_cannot_access_user_resource(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        $this->actingAs($servant);

        $this->assertFalse(UserResource::canAccess());
    }
}
