<?php

namespace Tests\Unit\Policies;

use App\Models\ServiceGroup;
use App\Policies\ServiceGroupPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class ServiceGroupPolicyTest extends TestCase
{
    use RefreshDatabase, CreatesTestUsers;

    private ServiceGroupPolicy $policy;
    private ServiceGroup $groupA;
    private ServiceGroup $groupB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ServiceGroupPolicy();
        $this->groupA = ServiceGroup::factory()->create();
        $this->groupB = ServiceGroup::factory()->create();
    }

    public function test_super_admin_full_access(): void
    {
        $admin = $this->createSuperAdmin();
        $this->assertTrue($this->policy->viewAny($admin));
        $this->assertTrue($this->policy->view($admin, $this->groupA));
        $this->assertTrue($this->policy->create($admin));
        $this->assertTrue($this->policy->update($admin, $this->groupA));
        $this->assertTrue($this->policy->delete($admin, $this->groupA));
        $this->assertTrue($this->policy->manageRegistrationLink($admin, $this->groupA));
    }

    public function test_service_leader_can_manage_but_not_delete(): void
    {
        $leader = $this->createServiceLeader();
        $this->assertTrue($this->policy->viewAny($leader));
        $this->assertTrue($this->policy->create($leader));
        $this->assertTrue($this->policy->update($leader, $this->groupA));
        $this->assertFalse($this->policy->delete($leader, $this->groupA));
    }

    public function test_family_leader_scoped_to_own_group(): void
    {
        $fl = $this->createFamilyLeader($this->groupA);
        $this->assertTrue($this->policy->viewAny($fl));
        $this->assertTrue($this->policy->view($fl, $this->groupA));
        $this->assertFalse($this->policy->view($fl, $this->groupB));
        $this->assertTrue($this->policy->update($fl, $this->groupA));
        $this->assertFalse($this->policy->update($fl, $this->groupB));
        $this->assertFalse($this->policy->delete($fl, $this->groupA));
    }

    public function test_family_leader_manage_registration_link_own_group(): void
    {
        $fl = $this->createFamilyLeader($this->groupA);
        $this->assertTrue($this->policy->manageRegistrationLink($fl, $this->groupA));
        $this->assertFalse($this->policy->manageRegistrationLink($fl, $this->groupB));
    }

    public function test_servant_cannot_access(): void
    {
        $servant = $this->createServant($this->groupA);
        $this->assertFalse($this->policy->viewAny($servant));
        $this->assertFalse($this->policy->create($servant));
        $this->assertFalse($this->policy->update($servant, $this->groupA));
        $this->assertFalse($this->policy->delete($servant, $this->groupA));
        $this->assertFalse($this->policy->manageRegistrationLink($servant, $this->groupA));
    }
}
