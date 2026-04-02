<?php

namespace Tests\Feature;

use App\Filament\Resources\Beneficiaries\BeneficiaryResource;
use App\Filament\Resources\MedicalFiles\MedicalFileResource;
use App\Filament\Resources\PrayerRequests\PrayerRequestResource;
use App\Filament\Resources\ScheduledVisits\ScheduledVisitResource;
use App\Filament\Resources\ServiceGroups\ServiceGroupResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Visits\VisitResource;
use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServantReadOnlyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function servant_cannot_create_in_any_resource()
    {
        $serviceGroup = ServiceGroup::factory()->create();
        $servant      = User::factory()->create([
            'role'             => 'servant',
            'service_group_id' => $serviceGroup->id,
        ]);

        $this->actingAs($servant);

        // Servant CANNOT create these
        $this->assertFalse(BeneficiaryResource::canCreate(), 'Servant should not create beneficiaries');
        $this->assertFalse(ScheduledVisitResource::canCreate(), 'Servant should not create scheduled visits');
        $this->assertFalse(MedicalFileResource::canCreate(), 'Servant should not create medical files');

        // Servant CAN create these (special permissions)
        $this->assertTrue(VisitResource::canCreate(), 'Servant should create visits');
        $this->assertTrue(PrayerRequestResource::canCreate(), 'Servant should create prayer requests');
    }

    /** @test */
    public function servant_cannot_edit_in_any_resource()
    {
        $serviceGroup = ServiceGroup::factory()->create();
        $servant      = User::factory()->create([
            'role'             => 'servant',
            'service_group_id' => $serviceGroup->id,
        ]);

        $beneficiary = Beneficiary::factory()->create([
            'service_group_id'    => $serviceGroup->id,
            'assigned_servant_id' => $servant->id,
        ]);

        $visit = Visit::factory()->create([
            'beneficiary_id' => $beneficiary->id,
            'created_by'     => $servant->id,
        ]);

        $this->actingAs($servant);

        // Test all resources
        $this->assertFalse(BeneficiaryResource::canEdit($beneficiary), 'Servant should not edit beneficiaries');
        $this->assertFalse(VisitResource::canEdit($visit), 'Servant should not edit visits');
    }

    /** @test */
    public function servant_cannot_delete_in_any_resource()
    {
        $serviceGroup = ServiceGroup::factory()->create();
        $servant      = User::factory()->create([
            'role'             => 'servant',
            'service_group_id' => $serviceGroup->id,
        ]);

        $beneficiary = Beneficiary::factory()->create([
            'service_group_id'    => $serviceGroup->id,
            'assigned_servant_id' => $servant->id,
        ]);

        $visit = Visit::factory()->create([
            'beneficiary_id' => $beneficiary->id,
            'created_by'     => $servant->id,
        ]);

        $this->actingAs($servant);

        // Test all resources
        $this->assertFalse(BeneficiaryResource::canDelete($beneficiary), 'Servant should not delete beneficiaries');
        $this->assertFalse(VisitResource::canDelete($visit), 'Servant should not delete visits');
    }

    /** @test */
    public function servant_can_view_all_resources()
    {
        $serviceGroup = ServiceGroup::factory()->create();
        $servant      = User::factory()->create([
            'role'             => 'servant',
            'service_group_id' => $serviceGroup->id,
        ]);

        $beneficiary = Beneficiary::factory()->create([
            'service_group_id'    => $serviceGroup->id,
            'assigned_servant_id' => $servant->id,
        ]);

        $visit = Visit::factory()->create([
            'beneficiary_id' => $beneficiary->id,
            'created_by'     => $servant->id,
        ]);

        $this->actingAs($servant);

        // Servant can view
        $this->assertTrue(BeneficiaryResource::canView($beneficiary), 'Servant should view beneficiaries');
        $this->assertTrue(VisitResource::canView($visit), 'Servant should view visits');
    }

    /** @test */
    public function servant_cannot_access_service_groups_or_users()
    {
        $serviceGroup = ServiceGroup::factory()->create();
        $servant      = User::factory()->create([
            'role'             => 'servant',
            'service_group_id' => $serviceGroup->id,
        ]);

        $this->actingAs($servant);

        // Servant cannot access these resources at all
        $this->assertFalse(ServiceGroupResource::canAccess(), 'Servant should not access service groups');
        $this->assertFalse(UserResource::canAccess(), 'Servant should not access users');
    }

    /** @test */
    public function family_leader_can_modify_all_resources()
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

        // Family leader can create, edit, delete
        $this->assertTrue(BeneficiaryResource::canCreate());
        $this->assertTrue(BeneficiaryResource::canEdit($beneficiary));
        $this->assertTrue(BeneficiaryResource::canDelete($beneficiary));

        $this->assertTrue(VisitResource::canCreate());
        $this->assertTrue(ScheduledVisitResource::canCreate());
        $this->assertTrue(MedicalFileResource::canCreate());
        $this->assertTrue(PrayerRequestResource::canCreate());
    }

    /** @test */
    public function super_admin_has_full_access()
    {
        $admin       = User::factory()->create(['role' => 'super_admin']);
        $beneficiary = Beneficiary::factory()->create();

        $this->actingAs($admin);

        // Super admin has full access
        $this->assertTrue(BeneficiaryResource::canCreate());
        $this->assertTrue(BeneficiaryResource::canEdit($beneficiary));
        $this->assertTrue(BeneficiaryResource::canDelete($beneficiary));
        $this->assertTrue(BeneficiaryResource::canView($beneficiary));

        $this->assertTrue(ServiceGroupResource::canAccess());
        $this->assertTrue(UserResource::canAccess());
    }
}
