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

    public function test_super_admin_has_full_access()
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

    public function test_service_leader_has_full_access()
    {
        $serviceGroup  = ServiceGroup::factory()->create();
        $serviceLeader = User::factory()->create([
            'role'             => 'service_leader',
            'service_group_id' => $serviceGroup->id,
        ]);
        $beneficiary = Beneficiary::factory()->create([
            'service_group_id' => $serviceGroup->id,
        ]);

        $this->assertTrue($this->policy->viewAny($serviceLeader));
        $this->assertTrue($this->policy->view($serviceLeader, $beneficiary));
        $this->assertTrue($this->policy->create($serviceLeader));
        $this->assertTrue($this->policy->update($serviceLeader, $beneficiary));
        $this->assertTrue($this->policy->delete($serviceLeader, $beneficiary));
    }

    public function test_family_leader_has_service_group_scoped_access()
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

        // Can view any beneficiaries (scoped by resource)
        $this->assertTrue($this->policy->viewAny($familyLeader));

        // Can view beneficiaries in same service group
        $this->assertTrue($this->policy->view($familyLeader, $beneficiaryInSameGroup));

        // Cannot view beneficiaries in different service group
        $this->assertFalse($this->policy->view($familyLeader, $beneficiaryInDifferentGroup));

        // Can create beneficiaries
        $this->assertTrue($this->policy->create($familyLeader));

        // Can update beneficiaries in same service group
        $this->assertTrue($this->policy->update($familyLeader, $beneficiaryInSameGroup));

        // Cannot update beneficiaries in different service group
        $this->assertFalse($this->policy->update($familyLeader, $beneficiaryInDifferentGroup));

        // Can delete beneficiaries in same service group
        $this->assertTrue($this->policy->delete($familyLeader, $beneficiaryInSameGroup));
    }

    public function test_servant_has_limited_access()
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

        // Can view any beneficiaries (scoped by resource)
        $this->assertTrue($this->policy->viewAny($servant));

        // Can view beneficiaries in same service group
        $this->assertTrue($this->policy->view($servant, $beneficiaryInSameGroup));

        // Cannot view beneficiaries in different service group
        $this->assertFalse($this->policy->view($servant, $beneficiaryInDifferentGroup));

        // Cannot create beneficiaries
        $this->assertFalse($this->policy->create($servant));

        // Cannot update beneficiaries
        $this->assertFalse($this->policy->update($servant, $beneficiaryInSameGroup));

        // Cannot delete beneficiaries
        $this->assertFalse($this->policy->delete($servant, $beneficiaryInSameGroup));
    }
}
