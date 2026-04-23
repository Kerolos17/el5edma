<?php

namespace Tests\Unit\Policies;

use App\Models\ServiceGroup;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class UserPolicyTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    private UserPolicy $policy;
    private ServiceGroup $groupA;
    private ServiceGroup $groupB;
    private User $serviceLeader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy        = new UserPolicy;
        $this->groupA        = ServiceGroup::factory()->create();
        $this->groupB        = ServiceGroup::factory()->create();
        $this->serviceLeader = $this->createServiceLeader();
        $this->groupA->update(['service_leader_id' => $this->serviceLeader->id]);
    }

    public function test_super_admin_can_manage_all_users(): void
    {
        $admin  = $this->createSuperAdmin();
        $target = $this->createServant($this->groupA);

        $this->assertTrue($this->policy->viewAny($admin));
        $this->assertTrue($this->policy->view($admin, $target));
        $this->assertTrue($this->policy->create($admin));
        $this->assertTrue($this->policy->update($admin, $target));
        $this->assertTrue($this->policy->delete($admin, $target));
        $this->assertTrue($this->policy->assignRole($admin, $target));
        $this->assertTrue($this->policy->manageServiceGroup($admin, $target));
    }

    public function test_user_cannot_delete_self(): void
    {
        $admin = $this->createSuperAdmin();
        $this->assertFalse($this->policy->delete($admin, $admin));
        $this->assertFalse($this->policy->forceDelete($admin, $admin));
    }

    public function test_user_can_view_and_update_self(): void
    {
        $servant = $this->createServant($this->groupA);
        $this->assertTrue($this->policy->view($servant, $servant));
        $this->assertTrue($this->policy->update($servant, $servant));
    }

    public function test_servant_cannot_view_any_or_create(): void
    {
        $servant = $this->createServant($this->groupA);
        $this->assertFalse($this->policy->viewAny($servant));
        $this->assertFalse($this->policy->create($servant));
    }

    public function test_family_leader_can_view_same_group(): void
    {
        $fl     = $this->createFamilyLeader($this->groupA);
        $member = $this->createServant($this->groupA);
        $other  = $this->createServant(ServiceGroup::factory()->create());

        $this->assertTrue($this->policy->view($fl, $member));
        $this->assertFalse($this->policy->view($fl, $other));
    }

    public function test_service_leader_can_only_view_users_in_managed_groups(): void
    {
        $inScope  = $this->createServant($this->groupA);
        $outScope = $this->createServant($this->groupB);

        $this->assertTrue($this->policy->view($this->serviceLeader, $inScope));
        $this->assertFalse($this->policy->view($this->serviceLeader, $outScope));
    }

    public function test_service_leader_can_update_managed_family_leaders_and_servants_only(): void
    {
        $familyLeader      = $this->createFamilyLeader($this->groupA);
        $servant           = $this->createServant($this->groupA);
        $otherGroupServant = $this->createServant($this->groupB);

        $this->assertTrue($this->policy->update($this->serviceLeader, $familyLeader));
        $this->assertTrue($this->policy->update($this->serviceLeader, $servant));
        $this->assertFalse($this->policy->update($this->serviceLeader, $otherGroupServant));
        $this->assertFalse($this->policy->delete($this->serviceLeader, $servant));
    }

    public function test_service_leader_cannot_assign_roles(): void
    {
        $target = $this->createServant($this->groupA);
        $this->assertFalse($this->policy->assignRole($this->serviceLeader, $target));
    }

    public function test_family_leader_cannot_manage_other_users(): void
    {
        $familyLeader = $this->createFamilyLeader($this->groupA);
        $member       = $this->createServant($this->groupA);

        $this->assertTrue($this->policy->view($familyLeader, $member));
        $this->assertFalse($this->policy->create($familyLeader));
        $this->assertFalse($this->policy->update($familyLeader, $member));
        $this->assertFalse($this->policy->delete($familyLeader, $member));
    }

    public function test_user_cannot_assign_role_to_self(): void
    {
        $admin = $this->createSuperAdmin();
        $this->assertFalse($this->policy->assignRole($admin, $admin));
    }

    public function test_user_cannot_manage_own_service_group(): void
    {
        $admin = $this->createSuperAdmin();
        $this->assertFalse($this->policy->manageServiceGroup($admin, $admin));
    }
}
