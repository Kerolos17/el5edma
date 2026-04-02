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
    use RefreshDatabase, CreatesTestUsers;

    private UserPolicy $policy;
    private ServiceGroup $groupA;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new UserPolicy();
        $this->groupA = ServiceGroup::factory()->create();
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

    public function test_service_leader_cannot_assign_roles(): void
    {
        $leader = $this->createServiceLeader();
        $target = $this->createServant($this->groupA);
        $this->assertFalse($this->policy->assignRole($leader, $target));
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
