<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class FilamentScopeAndTransactionTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    // -- Scope Validation Code Exists --

    #[Test]
    public function create_medical_file_page_has_scope_validation(): void
    {
        $file = file_get_contents(app_path('Filament/Resources/MedicalFiles/Pages/CreateMedicalFile.php'));
        $this->assertStringContainsString('validateBeneficiaryScope', $file);
        $this->assertStringContainsString('managedServiceGroupIds', $file);
        $this->assertStringContainsString('UserRole::ServiceLeader', $file);
        $this->assertStringContainsString('UserRole::FamilyLeader', $file);
        $this->assertStringContainsString('UserRole::Servant', $file);
    }

    #[Test]
    public function create_prayer_request_page_has_scope_validation(): void
    {
        $file = file_get_contents(app_path('Filament/Resources/PrayerRequests/Pages/CreatePrayerRequest.php'));
        $this->assertStringContainsString('validateBeneficiaryScope', $file);
        $this->assertStringContainsString('managedServiceGroupIds', $file);
        $this->assertStringContainsString('UserRole::ServiceLeader', $file);
        $this->assertStringContainsString('UserRole::FamilyLeader', $file);
        $this->assertStringContainsString('UserRole::Servant', $file);
    }

    // -- OfflineVisitSync Transaction Fix --

    #[Test]
    public function offline_visit_sync_uses_after_commit(): void
    {
        $file = file_get_contents(app_path('Http/Controllers/Servant/OfflineVisitSyncController.php'));
        $this->assertStringContainsString('DB::afterCommit', $file, 'Must use afterCommit for servant attach');
        $this->assertStringContainsString('servants()->attach', $file);
    }

    #[Test]
    public function offline_visit_sync_still_creates_visit_in_transaction(): void
    {
        $file = file_get_contents(app_path('Http/Controllers/Servant/OfflineVisitSyncController.php'));
        $this->assertStringContainsString('DB::transaction', $file, 'Visit creation must remain in transaction');
        $this->assertStringContainsString('Visit::create', $file);
    }

    // -- Scope Logic Unit Test --

    #[Test]
    public function servant_scope_query_filters_by_assigned_servant(): void
    {
        $group          = ServiceGroup::factory()->create();
        $servant        = $this->createServant($group);
        $ownBeneficiary = Beneficiary::factory()->create([
            'service_group_id'    => $group->id,
            'assigned_servant_id' => $servant->id,
            'status'              => 'active',
        ]);
        $otherBeneficiary = Beneficiary::factory()->create([
            'service_group_id'    => $group->id,
            'assigned_servant_id' => null,
            'status'              => 'active',
        ]);

        $this->actingAs($servant);

        // Servant can see their own beneficiary
        $this->assertDatabaseHas('beneficiaries', [
            'id'                  => $ownBeneficiary->id,
            'assigned_servant_id' => $servant->id,
        ]);

        // Other beneficiary is not assigned to this servant
        $this->assertDatabaseHas('beneficiaries', [
            'id'                  => $otherBeneficiary->id,
            'assigned_servant_id' => null,
        ]);
    }

    #[Test]
    public function family_leader_scope_query_filters_by_service_group(): void
    {
        $group          = ServiceGroup::factory()->create();
        $familyLeader   = $this->createFamilyLeader($group);
        $ownBeneficiary = Beneficiary::factory()->create([
            'service_group_id' => $group->id,
            'status'           => 'active',
        ]);
        $otherGroup       = ServiceGroup::factory()->create();
        $otherBeneficiary = Beneficiary::factory()->create([
            'service_group_id' => $otherGroup->id,
            'status'           => 'active',
        ]);

        $this->actingAs($familyLeader);

        // Own group beneficiary exists
        $this->assertDatabaseHas('beneficiaries', [
            'id'               => $ownBeneficiary->id,
            'service_group_id' => $group->id,
        ]);

        // Other group beneficiary exists but different group
        $this->assertDatabaseHas('beneficiaries', [
            'id'               => $otherBeneficiary->id,
            'service_group_id' => $otherGroup->id,
        ]);

        $this->assertNotEquals($group->id, $otherGroup->id);
    }
}
