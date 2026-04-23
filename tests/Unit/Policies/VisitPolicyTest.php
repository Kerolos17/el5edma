<?php

namespace Tests\Unit\Policies;

use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Models\Visit;
use App\Policies\VisitPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class VisitPolicyTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    private VisitPolicy $policy;
    private ServiceGroup $groupA;
    private ServiceGroup $groupB;
    private Visit $visitA;
    private Visit $visitB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new VisitPolicy;
        $this->groupA = ServiceGroup::factory()->create();
        $this->groupB = ServiceGroup::factory()->create();

        $beneficiaryA = Beneficiary::factory()->create(['service_group_id' => $this->groupA->id]);
        $beneficiaryB = Beneficiary::factory()->create(['service_group_id' => $this->groupB->id]);

        $this->visitA = Visit::factory()->create(['beneficiary_id' => $beneficiaryA->id]);
        $this->visitB = Visit::factory()->create(['beneficiary_id' => $beneficiaryB->id]);
    }

    public function test_super_admin_full_access(): void
    {
        $admin = $this->createSuperAdmin();
        $this->assertTrue($this->policy->viewAny($admin));
        $this->assertTrue($this->policy->view($admin, $this->visitA));
        $this->assertTrue($this->policy->create($admin));
        $this->assertTrue($this->policy->update($admin, $this->visitA));
        $this->assertTrue($this->policy->delete($admin, $this->visitA));
        $this->assertTrue($this->policy->forceDelete($admin, $this->visitA));
    }

    public function test_service_leader_full_access_except_force_delete(): void
    {
        $leader = $this->createServiceLeader();
        $this->assertTrue($this->policy->view($leader, $this->visitA));
        $this->assertTrue($this->policy->update($leader, $this->visitA));
        $this->assertTrue($this->policy->delete($leader, $this->visitA));
        $this->assertFalse($this->policy->forceDelete($leader, $this->visitA));
    }

    public function test_family_leader_scoped_to_group(): void
    {
        $fl = $this->createFamilyLeader($this->groupA);
        $this->assertTrue($this->policy->view($fl, $this->visitA));
        $this->assertTrue($this->policy->update($fl, $this->visitA));
        $this->assertFalse($this->policy->view($fl, $this->visitB));
        $this->assertFalse($this->policy->update($fl, $this->visitB));
    }

    public function test_servant_can_create_but_not_update_or_delete(): void
    {
        $servant = $this->createServant($this->groupA);
        $this->assertTrue($this->policy->viewAny($servant));
        $this->assertTrue($this->policy->create($servant));
        $this->assertFalse($this->policy->update($servant, $this->visitA));
        $this->assertFalse($this->policy->delete($servant, $this->visitA));
    }

    public function test_servant_view_scoped_to_group(): void
    {
        $servant  = $this->createServant($this->groupA);
        $ownVisit = Visit::factory()->create([
            'beneficiary_id' => $this->visitA->beneficiary_id,
            'created_by'     => $servant->id,
        ]);

        $this->assertTrue($this->policy->view($servant, $ownVisit));
        $this->assertFalse($this->policy->view($servant, $this->visitA));
        $this->assertFalse($this->policy->view($servant, $this->visitB));
    }
}
