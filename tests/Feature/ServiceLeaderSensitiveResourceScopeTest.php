<?php

namespace Tests\Feature;

use App\Models\Beneficiary;
use App\Models\MedicalFile;
use App\Models\Medication;
use App\Models\PrayerRequest;
use App\Models\ServiceGroup;
use App\Policies\MedicalFilePolicy;
use App\Policies\MedicationPolicy;
use App\Policies\PrayerRequestPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class ServiceLeaderSensitiveResourceScopeTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    public function test_service_leader_cannot_access_sensitive_records_outside_managed_groups(): void
    {
        $leader       = $this->createServiceLeader();
        $managedGroup = ServiceGroup::factory()->create(['service_leader_id' => $leader->id]);
        $otherGroup   = ServiceGroup::factory()->create();

        $managedBeneficiary = Beneficiary::factory()->create([
            'service_group_id' => $managedGroup->id,
        ]);
        $otherBeneficiary = Beneficiary::factory()->create([
            'service_group_id' => $otherGroup->id,
        ]);

        $managedMedicalFile = MedicalFile::factory()->create([
            'beneficiary_id' => $managedBeneficiary->id,
        ]);
        $otherMedicalFile = MedicalFile::factory()->create([
            'beneficiary_id' => $otherBeneficiary->id,
        ]);

        $managedPrayer = PrayerRequest::factory()->create([
            'beneficiary_id' => $managedBeneficiary->id,
        ]);
        $otherPrayer = PrayerRequest::factory()->create([
            'beneficiary_id' => $otherBeneficiary->id,
        ]);

        $managedMedication = Medication::factory()->create([
            'beneficiary_id' => $managedBeneficiary->id,
        ]);
        $otherMedication = Medication::factory()->create([
            'beneficiary_id' => $otherBeneficiary->id,
        ]);

        $medicalPolicy = new MedicalFilePolicy;
        $prayerPolicy  = new PrayerRequestPolicy;
        $medPolicy     = new MedicationPolicy;

        $this->assertTrue($medicalPolicy->view($leader, $managedMedicalFile));
        $this->assertTrue($medicalPolicy->delete($leader, $managedMedicalFile));
        $this->assertFalse($medicalPolicy->view($leader, $otherMedicalFile));
        $this->assertFalse($medicalPolicy->delete($leader, $otherMedicalFile));

        $this->assertTrue($prayerPolicy->view($leader, $managedPrayer));
        $this->assertTrue($prayerPolicy->update($leader, $managedPrayer));
        $this->assertFalse($prayerPolicy->view($leader, $otherPrayer));
        $this->assertFalse($prayerPolicy->update($leader, $otherPrayer));

        $this->assertTrue($medPolicy->view($leader, $managedMedication));
        $this->assertTrue($medPolicy->update($leader, $managedMedication));
        $this->assertFalse($medPolicy->view($leader, $otherMedication));
        $this->assertFalse($medPolicy->update($leader, $otherMedication));
    }
}
