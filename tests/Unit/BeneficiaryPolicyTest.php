<?php

namespace Tests\Unit;

use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Policies\BeneficiaryPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BeneficiaryPolicyTest extends TestCase
{
    use RefreshDatabase;

    private BeneficiaryPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new BeneficiaryPolicy;
    }

    public function test_super_admin_has_full_access(): void
    {
        $serviceGroup = ServiceGroup::factory()->create();
        $superAdmin   = User::factory()->create([
            'role'             => 'super_admin',
            'service_group_id' => $serviceGroup->id,
        ]);
        $beneficiary = Beneficiary::factory()->create([
            'service_group_id' => $serviceGroup->id,
        ]);

        $this->assertTrue($this->policy->viewAny($superAdmin));
        $this->assertTrue($this->policy->view($superAdmin, $beneficiary));
        $this->assertTrue($this->policy->create($superAdmin));
        $this->assertTrue($this->policy->update($superAdmin, $beneficiary));
        $this->assertTrue($this->policy->delete($superAdmin, $beneficiary));
    }

    public function test_service_leader_is_scoped_to_managed_groups(): void
    {
        $managedGroup  = ServiceGroup::factory()->create();
        $otherGroup    = ServiceGroup::factory()->create();
        $serviceLeader = User::factory()->create([
            'role'      => 'service_leader',
            'is_active' => true,
        ]);
        $managedGroup->update(['service_leader_id' => $serviceLeader->id]);

        $managedBeneficiary = Beneficiary::factory()->create([
            'service_group_id' => $managedGroup->id,
        ]);
        $otherBeneficiary = Beneficiary::factory()->create([
            'service_group_id' => $otherGroup->id,
        ]);

        $this->assertTrue($this->policy->viewAny($serviceLeader));
        $this->assertTrue($this->policy->create($serviceLeader));
        $this->assertTrue($this->policy->view($serviceLeader, $managedBeneficiary));
        $this->assertTrue($this->policy->update($serviceLeader, $managedBeneficiary));
        $this->assertTrue($this->policy->delete($serviceLeader, $managedBeneficiary));
        $this->assertFalse($this->policy->view($serviceLeader, $otherBeneficiary));
        $this->assertFalse($this->policy->update($serviceLeader, $otherBeneficiary));
        $this->assertFalse($this->policy->delete($serviceLeader, $otherBeneficiary));
    }

    public function test_family_leader_has_service_group_scoped_access(): void
    {
        $serviceGroup1 = ServiceGroup::factory()->create();
        $serviceGroup2 = ServiceGroup::factory()->create();

        $familyLeader = User::factory()->create([
            'role'             => 'family_leader',
            'service_group_id' => $serviceGroup1->id,
        ]);

        $beneficiaryInSameGroup = Beneficiary::factory()->create([
            'service_group_id' => $serviceGroup1->id,
        ]);

        $beneficiaryInDifferentGroup = Beneficiary::factory()->create([
            'service_group_id' => $serviceGroup2->id,
        ]);

        $this->assertTrue($this->policy->viewAny($familyLeader));
        $this->assertTrue($this->policy->view($familyLeader, $beneficiaryInSameGroup));
        $this->assertFalse($this->policy->view($familyLeader, $beneficiaryInDifferentGroup));
        $this->assertTrue($this->policy->create($familyLeader));
        $this->assertTrue($this->policy->update($familyLeader, $beneficiaryInSameGroup));
        $this->assertFalse($this->policy->update($familyLeader, $beneficiaryInDifferentGroup));
        $this->assertTrue($this->policy->delete($familyLeader, $beneficiaryInSameGroup));
    }

    public function test_servant_has_limited_access(): void
    {
        $serviceGroup1 = ServiceGroup::factory()->create();
        $serviceGroup2 = ServiceGroup::factory()->create();

        $servant = User::factory()->create([
            'role'             => 'servant',
            'service_group_id' => $serviceGroup1->id,
        ]);

        $beneficiaryInSameGroup = Beneficiary::factory()->create([
            'service_group_id' => $serviceGroup1->id,
        ]);

        $beneficiaryInDifferentGroup = Beneficiary::factory()->create([
            'service_group_id' => $serviceGroup2->id,
        ]);

        $this->assertTrue($this->policy->viewAny($servant));
        $this->assertTrue($this->policy->view($servant, $beneficiaryInSameGroup));
        $this->assertFalse($this->policy->view($servant, $beneficiaryInDifferentGroup));
        $this->assertFalse($this->policy->create($servant));
        $this->assertFalse($this->policy->update($servant, $beneficiaryInSameGroup));
        $this->assertFalse($this->policy->delete($servant, $beneficiaryInSameGroup));
    }
}
