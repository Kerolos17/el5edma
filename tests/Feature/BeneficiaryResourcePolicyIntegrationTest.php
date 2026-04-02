<?php

namespace Tests\Feature;

use App\Filament\Resources\Beneficiaries\BeneficiaryResource;
use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BeneficiaryResourcePolicyIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_beneficiary_resource_uses_policy_for_authorization()
    {
        $serviceGroup = ServiceGroup::factory()->create();

        // Test super_admin
        $superAdmin = User::factory()->create([
            'role'             => 'super_admin',
            'service_group_id' => $serviceGroup->id,
        ]);
        $beneficiary = Beneficiary::factory()->create([
            'service_group_id' => $serviceGroup->id,
        ]);

        $this->actingAs($superAdmin);

        $this->assertTrue(BeneficiaryResource::canCreate());
        $this->assertTrue(BeneficiaryResource::canEdit($beneficiary));
        $this->assertTrue(BeneficiaryResource::canDelete($beneficiary));
        $this->assertTrue(BeneficiaryResource::canView($beneficiary));
    }

    public function test_servant_cannot_create_edit_delete_through_resource()
    {
        $serviceGroup = ServiceGroup::factory()->create();

        $servant = User::factory()->create([
            'role'             => 'servant',
            'service_group_id' => $serviceGroup->id,
        ]);
        $beneficiary = Beneficiary::factory()->create([
            'service_group_id' => $serviceGroup->id,
        ]);

        $this->actingAs($servant);

        $this->assertFalse(BeneficiaryResource::canCreate());
        $this->assertFalse(BeneficiaryResource::canEdit($beneficiary));
        $this->assertFalse(BeneficiaryResource::canDelete($beneficiary));
        $this->assertTrue(BeneficiaryResource::canView($beneficiary));
    }

    public function test_family_leader_can_create_edit_and_delete()
    {
        $serviceGroup = ServiceGroup::factory()->create();

        $familyLeader = User::factory()->create([
            'role'             => 'family_leader',
            'service_group_id' => $serviceGroup->id,
        ]);
        $beneficiary = Beneficiary::factory()->create([
            'service_group_id' => $serviceGroup->id,
        ]);

        $this->actingAs($familyLeader);

        $this->assertTrue(BeneficiaryResource::canCreate());
        $this->assertTrue(BeneficiaryResource::canEdit($beneficiary));
        $this->assertTrue(BeneficiaryResource::canDelete($beneficiary));
        $this->assertTrue(BeneficiaryResource::canView($beneficiary));
    }

    public function test_family_leader_cannot_edit_beneficiary_from_different_service_group()
    {
        $serviceGroup1 = ServiceGroup::factory()->create();
        $serviceGroup2 = ServiceGroup::factory()->create();

        $familyLeader = User::factory()->create([
            'role'             => 'family_leader',
            'service_group_id' => $serviceGroup1->id,
        ]);
        $beneficiaryFromDifferentGroup = Beneficiary::factory()->create([
            'service_group_id' => $serviceGroup2->id,
        ]);

        $this->actingAs($familyLeader);

        $this->assertFalse(BeneficiaryResource::canEdit($beneficiaryFromDifferentGroup));
        $this->assertFalse(BeneficiaryResource::canView($beneficiaryFromDifferentGroup));
    }
}
