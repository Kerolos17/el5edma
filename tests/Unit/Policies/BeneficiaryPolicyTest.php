<?php

namespace Tests\Unit\Policies;

use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Policies\BeneficiaryPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class BeneficiaryPolicyTest extends TestCase
{
    use RefreshDatabase, CreatesTestUsers;

    private BeneficiaryPolicy $policy;
    private ServiceGroup $groupA;
    private ServiceGroup $groupB;
    private Beneficiary $beneficiaryA;
    private Beneficiary $beneficiaryB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new BeneficiaryPolicy();
        $this->groupA = ServiceGroup::factory()->create();
        $this->groupB = ServiceGroup::factory()->create();
        $this->beneficiaryA = Beneficiary::factory()->create(['service_group_id' => $this->groupA->id]);
        $this->beneficiaryB = Beneficiary::factory()->create(['service_group_id' => $this->groupB->id]);
    }

    public function test_super_admin_can_do_everything(): void
    {
        $admin = $this->createSuperAdmin();
        $this->assertTrue($this->policy->viewAny($admin));
        $this->assertTrue($this->policy->view($admin, $this->beneficiaryA));
        $this->assertTrue($this->policy->create($admin));
        $this->assertTrue($this->policy->update($admin, $this->beneficiaryA));
        $this->assertTrue($this->policy->delete($admin, $this->beneficiaryA));
        $this->assertTrue($this->policy->forceDelete($admin, $this->beneficiaryA));
    }

    public function test_service_leader_can_do_everything_except_force_delete(): void
    {
        $leader = $this->createServiceLeader();
        $this->assertTrue($this->policy->viewAny($leader));
        $this->assertTrue($this->policy->view($leader, $this->beneficiaryA));
        $this->assertTrue($this->policy->create($leader));
        $this->assertTrue($this->policy->update($leader, $this->beneficiaryA));
        $this->assertTrue($this->policy->delete($leader, $this->beneficiaryA));
        $this->assertFalse($this->policy->forceDelete($leader, $this->beneficiaryA));
    }

    public function test_family_leader_can_manage_own_group(): void
    {
        $fl = $this->createFamilyLeader($this->groupA);
        $this->assertTrue($this->policy->viewAny($fl));
        $this->assertTrue($this->policy->view($fl, $this->beneficiaryA));
        $this->assertTrue($this->policy->create($fl));
        $this->assertTrue($this->policy->update($fl, $this->beneficiaryA));
        $this->assertTrue($this->policy->delete($fl, $this->beneficiaryA));
    }

    public function test_family_leader_cannot_manage_other_group(): void
    {
        $fl = $this->createFamilyLeader($this->groupA);
        $this->assertFalse($this->policy->view($fl, $this->beneficiaryB));
        $this->assertFalse($this->policy->update($fl, $this->beneficiaryB));
        $this->assertFalse($this->policy->delete($fl, $this->beneficiaryB));
    }

    public function test_servant_can_view_own_group_only(): void
    {
        $servant = $this->createServant($this->groupA);
        $this->assertTrue($this->policy->viewAny($servant));
        $this->assertTrue($this->policy->view($servant, $this->beneficiaryA));
        $this->assertFalse($this->policy->view($servant, $this->beneficiaryB));
    }

    public function test_servant_cannot_create_update_delete(): void
    {
        $servant = $this->createServant($this->groupA);
        $this->assertFalse($this->policy->create($servant));
        $this->assertFalse($this->policy->update($servant, $this->beneficiaryA));
        $this->assertFalse($this->policy->delete($servant, $this->beneficiaryA));
    }
}
