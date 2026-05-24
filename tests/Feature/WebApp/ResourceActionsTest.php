<?php

declare(strict_types=1);

namespace Tests\Feature\WebApp;

use App\Enums\UserRole;
use App\Livewire\WebApp\BeneficiariesPage;
use App\Livewire\WebApp\MedicalFilesPage;

use App\Livewire\WebApp\PrayerRequestsPage;
use App\Livewire\WebApp\ScheduledVisitsPage;
use App\Livewire\WebApp\ServiceGroupsPage;
use App\Livewire\WebApp\UsersPage;
use App\Livewire\WebApp\VisitsPage;
use App\Models\Beneficiary;
use App\Models\PrayerRequest;
use App\Models\ScheduledVisit;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class ResourceActionsTest extends TestCase
{
    use CreatesTestUsers;
    use RefreshDatabase;

    #[Test]
    public function servant_can_create_visit_from_web_app_page(): void
    {
        $group = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $beneficiary = Beneficiary::factory()->create([
            'assigned_servant_id' => $servant->id,
            'service_group_id' => $group->id,
        ]);

        Livewire::actingAs($servant)
            ->test(VisitsPage::class)
            ->call('openVisitForm', $beneficiary->id)
            ->set('visitType', 'home_visit')
            ->set('beneficiaryStatus', 'good')
            ->set('durationMinutes', 45)
            ->set('visitFeedback', 'تمت متابعة الحالة')
            ->call('saveVisit')
            ->assertDispatched('toast')
            ->assertSet('showVisitForm', false);

        $this->assertDatabaseHas('visits', [
            'beneficiary_id' => $beneficiary->id,
            'type' => 'home_visit',
            'beneficiary_status' => 'good',
            'created_by' => $servant->id,
        ]);
    }

    #[Test]
    public function dedicated_visits_page_can_create_visit(): void
    {
        $group = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $beneficiary = Beneficiary::factory()->create([
            'assigned_servant_id' => $servant->id,
            'service_group_id' => $group->id,
        ]);

        Livewire::actingAs($servant)
            ->test(VisitsPage::class)
            ->call('openVisitForm', $beneficiary->id)
            ->set('visitType', 'phone_call')
            ->set('beneficiaryStatus', 'needs_follow')
            ->set('visitFeedback', 'Follow-up needed from dedicated visits page')
            ->call('saveVisit')
            ->assertDispatched('toast')
            ->assertSet('showVisitForm', false);

        $this->assertDatabaseHas('visits', [
            'beneficiary_id' => $beneficiary->id,
            'type' => 'phone_call',
            'beneficiary_status' => 'needs_follow',
            'feedback' => 'Follow-up needed from dedicated visits page',
            'created_by' => $servant->id,
        ]);
    }

    #[Test]
    public function servant_cannot_create_visit_for_out_of_scope_beneficiary_from_web_app(): void
    {
        $group = ServiceGroup::factory()->create();
        $otherGroup = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $beneficiary = Beneficiary::factory()->create([
            'service_group_id' => $otherGroup->id,
            'assigned_servant_id' => null,
        ]);

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($servant)
            ->test(VisitsPage::class)
            ->set('visitBeneficiaryId', $beneficiary->id)
            ->set('visitType', 'home_visit')
            ->set('visitDate', now()->format('Y-m-d\TH:i'))
            ->set('beneficiaryStatus', 'good')
            ->call('saveVisit');
    }

    #[Test]
    public function family_leader_can_update_visit_from_web_app(): void
    {
        $group = ServiceGroup::factory()->create();
        $familyLeader = $this->createFamilyLeader($group);
        $beneficiary = Beneficiary::factory()->create([
            'service_group_id' => $group->id,
        ]);
        $visit = Visit::factory()->create([
            'beneficiary_id' => $beneficiary->id,
            'type' => 'home_visit',
            'beneficiary_status' => 'good',
            'feedback' => 'Old feedback',
        ]);

        Livewire::actingAs($familyLeader)
            ->test(VisitsPage::class)
            ->call('editVisit', $visit->id)
            ->assertSet('editingVisitId', $visit->id)
            ->set('visitType', 'phone_call')
            ->set('visitDate', now()->subDay()->format('Y-m-d\TH:i'))
            ->set('beneficiaryStatus', 'needs_follow')
            ->set('visitFeedback', 'Updated feedback')
            ->set('needsFamilyLeader', true)
            ->call('saveVisit')
            ->assertDispatched('toast')
            ->assertSet('showVisitForm', false);

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'type' => 'phone_call',
            'beneficiary_status' => 'needs_follow',
            'feedback' => 'Updated feedback',
            'needs_family_leader' => true,
        ]);
    }

    #[Test]
    public function family_leader_can_resolve_visit_follow_up_from_web_app(): void
    {
        $group = ServiceGroup::factory()->create();
        $familyLeader = $this->createFamilyLeader($group);
        $beneficiary = Beneficiary::factory()->create([
            'service_group_id' => $group->id,
        ]);
        $visit = Visit::factory()->create([
            'beneficiary_id' => $beneficiary->id,
            'is_critical' => true,
            'needs_family_leader' => true,
            'needs_service_leader' => true,
            'critical_resolved_at' => null,
            'critical_resolved_by' => null,
        ]);

        Livewire::actingAs($familyLeader)
            ->test(VisitsPage::class)
            ->call('resolveVisitFollowUp', $visit->id)
            ->assertDispatched('toast');

        $visit->refresh();

        $this->assertFalse($visit->is_critical);
        $this->assertFalse($visit->needs_family_leader);
        $this->assertFalse($visit->needs_service_leader);
        $this->assertNotNull($visit->critical_resolved_at);
        $this->assertSame($familyLeader->id, $visit->critical_resolved_by);
    }

    #[Test]
    public function servant_cannot_update_visit_from_web_app(): void
    {
        $group = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $beneficiary = Beneficiary::factory()->create([
            'service_group_id' => $group->id,
        ]);
        $visit = Visit::factory()->create([
            'beneficiary_id' => $beneficiary->id,
            'created_by' => $servant->id,
        ]);

        Livewire::actingAs($servant)
            ->test(VisitsPage::class)
            ->call('editVisit', $visit->id)
            ->assertForbidden();
    }

    #[Test]
    public function family_leader_can_create_prayer_request_for_group_beneficiary_from_web_app(): void
    {
        $group = ServiceGroup::factory()->create();
        $familyLeader = $this->createFamilyLeader($group);
        $beneficiary = Beneficiary::factory()->create([
            'service_group_id' => $group->id,
        ]);

        Livewire::actingAs($familyLeader)
            ->test(PrayerRequestsPage::class)
            ->call('openPrayerForm', $beneficiary->id)
            ->set('prayerTitle', 'صلاة لأجل الشفاء')
            ->set('prayerBody', 'احتياج متابعة هذا الأسبوع')
            ->call('savePrayer')
            ->assertDispatched('toast')
            ->assertSet('showPrayerForm', false);

        $this->assertDatabaseHas('prayer_requests', [
            'beneficiary_id' => $beneficiary->id,
            'title' => 'صلاة لأجل الشفاء',
            'status' => 'open',
            'created_by' => $familyLeader->id,
        ]);
    }

    #[Test]
    public function dedicated_prayer_requests_page_can_create_prayer_request(): void
    {
        $group = ServiceGroup::factory()->create();
        $familyLeader = $this->createFamilyLeader($group);
        $beneficiary = Beneficiary::factory()->create([
            'service_group_id' => $group->id,
        ]);

        Livewire::actingAs($familyLeader)
            ->test(PrayerRequestsPage::class)
            ->call('openPrayerForm', $beneficiary->id)
            ->set('prayerTitle', 'Dedicated prayer request')
            ->set('prayerBody', 'Created from the dedicated prayer requests page')
            ->call('savePrayer')
            ->assertDispatched('toast')
            ->assertSet('showPrayerForm', false);

        $this->assertDatabaseHas('prayer_requests', [
            'beneficiary_id' => $beneficiary->id,
            'title' => 'Dedicated prayer request',
            'body' => 'Created from the dedicated prayer requests page',
            'status' => 'open',
            'created_by' => $familyLeader->id,
        ]);
    }

    #[Test]
    public function family_leader_can_mark_prayer_request_answered_from_web_app(): void
    {
        $group = ServiceGroup::factory()->create();
        $familyLeader = $this->createFamilyLeader($group);
        $beneficiary = Beneficiary::factory()->create([
            'service_group_id' => $group->id,
        ]);
        $prayerRequest = PrayerRequest::factory()->create([
            'beneficiary_id' => $beneficiary->id,
            'status' => 'open',
            'answered_at' => null,
        ]);

        Livewire::actingAs($familyLeader)
            ->test(PrayerRequestsPage::class)
            ->call('markPrayerAnswered', $prayerRequest->id)
            ->assertDispatched('toast');

        $prayerRequest->refresh();

        $this->assertSame('answered', $prayerRequest->status);
        $this->assertNotNull($prayerRequest->answered_at);
    }

    #[Test]
    public function family_leader_can_close_and_reopen_prayer_request_from_web_app(): void
    {
        $group = ServiceGroup::factory()->create();
        $familyLeader = $this->createFamilyLeader($group);
        $beneficiary = Beneficiary::factory()->create([
            'service_group_id' => $group->id,
        ]);
        $prayerRequest = PrayerRequest::factory()->create([
            'beneficiary_id' => $beneficiary->id,
            'status' => 'open',
        ]);

        Livewire::actingAs($familyLeader)
            ->test(PrayerRequestsPage::class)
            ->call('closePrayerRequest', $prayerRequest->id)
            ->assertDispatched('toast');

        $this->assertDatabaseHas('prayer_requests', [
            'id' => $prayerRequest->id,
            'status' => 'closed',
            'answered_at' => null,
        ]);

        Livewire::actingAs($familyLeader)
            ->test(PrayerRequestsPage::class)
            ->call('reopenPrayerRequest', $prayerRequest->id)
            ->assertDispatched('toast');

        $this->assertDatabaseHas('prayer_requests', [
            'id' => $prayerRequest->id,
            'status' => 'open',
            'answered_at' => null,
        ]);
    }

    #[Test]
    public function servant_cannot_update_prayer_request_status_from_web_app(): void
    {
        $group = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $beneficiary = Beneficiary::factory()->create([
            'assigned_servant_id' => $servant->id,
            'service_group_id' => $group->id,
        ]);
        $prayerRequest = PrayerRequest::factory()->create([
            'beneficiary_id' => $beneficiary->id,
            'created_by' => $servant->id,
            'status' => 'open',
        ]);

        Livewire::actingAs($servant)
            ->test(PrayerRequestsPage::class)
            ->call('markPrayerAnswered', $prayerRequest->id)
            ->assertForbidden();

        $this->assertDatabaseHas('prayer_requests', [
            'id' => $prayerRequest->id,
            'status' => 'open',
        ]);
    }

    #[Test]
    public function users_page_shows_create_actions_only_for_relevant_sections(): void
    {
        $group = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        Livewire::actingAs($servant)
            ->test(VisitsPage::class)
            ->assertSee('تسجيل زيارة')
            ->assertDontSee('إضافة طلب صلاة');

        Livewire::actingAs($servant)
            ->test(PrayerRequestsPage::class)
            ->assertSee('إضافة طلب صلاة');
    }

    #[Test]
    public function family_leader_can_create_beneficiary_from_web_app(): void
    {
        $group = ServiceGroup::factory()->create();
        $familyLeader = $this->createFamilyLeader($group);
        $servant = $this->createServant($group);

        Livewire::actingAs($familyLeader)
            ->test(BeneficiariesPage::class)
            ->call('openBeneficiaryForm')
            ->set('beneficiaryFullName', 'New Beneficiary')
            ->set('beneficiaryBirthDate', now()->subYears(12)->toDateString())
            ->set('beneficiaryGender', 'male')
            ->set('beneficiaryRecordStatus', 'active')
            ->set('beneficiaryAssignedServantId', $servant->id)
            ->set('beneficiaryPhone', '01000000000')
            ->call('saveBeneficiary')
            ->assertDispatched('toast')
            ->assertSet('showBeneficiaryForm', false);

        $this->assertDatabaseHas('beneficiaries', [
            'full_name' => 'New Beneficiary',
            'service_group_id' => $group->id,
            'assigned_servant_id' => $servant->id,
            'created_by' => $familyLeader->id,
        ]);
    }

    #[Test]
    public function dedicated_beneficiaries_page_can_create_beneficiary(): void
    {
        $group = ServiceGroup::factory()->create();
        $familyLeader = $this->createFamilyLeader($group);
        $servant = $this->createServant($group);

        Livewire::actingAs($familyLeader)
            ->test(BeneficiariesPage::class)
            ->call('openBeneficiaryForm')
            ->set('beneficiaryFullName', 'Dedicated Beneficiary')
            ->set('beneficiaryBirthDate', now()->subYears(9)->toDateString())
            ->set('beneficiaryGender', 'female')
            ->set('beneficiaryRecordStatus', 'active')
            ->set('beneficiaryAssignedServantId', $servant->id)
            ->call('saveBeneficiary')
            ->assertDispatched('toast')
            ->assertSet('showBeneficiaryForm', false);

        $this->assertDatabaseHas('beneficiaries', [
            'full_name' => 'Dedicated Beneficiary',
            'service_group_id' => $group->id,
            'assigned_servant_id' => $servant->id,
            'created_by' => $familyLeader->id,
        ]);
    }

    #[Test]
    public function beneficiary_form_preserves_full_filament_profile_fields(): void
    {
        Storage::fake('public');

        $group = ServiceGroup::factory()->create();
        $familyLeader = $this->createFamilyLeader($group);
        $servant = $this->createServant($group);
        $photo = UploadedFile::fake()->create('profile.png', 64, 'image/png');

        Livewire::actingAs($familyLeader)
            ->test(BeneficiariesPage::class)
            ->call('openBeneficiaryForm')
            ->set('beneficiaryPhoto', $photo)
            ->set('beneficiaryFullName', 'Complete Beneficiary')
            ->set('beneficiaryBirthDate', now()->subYears(13)->toDateString())
            ->set('beneficiaryGender', 'male')
            ->set('beneficiaryRecordStatus', 'active')
            ->set('beneficiaryAssignedServantId', $servant->id)
            ->set('beneficiaryPhone', '01289012345')
            ->set('beneficiaryWhatsapp', '01289012345')
            ->set('beneficiaryFacebookUrl', 'https://facebook.com/example')
            ->set('beneficiaryInstagramUrl', 'https://instagram.com/example')
            ->set('beneficiaryGuardianName', 'Guardian Name')
            ->set('beneficiaryGuardianPhone', '01590123456')
            ->set('beneficiaryGuardianRelation', 'Father')
            ->set('beneficiaryFatherStatus', 'alive')
            ->set('beneficiaryMotherStatus', 'deceased')
            ->set('beneficiaryMotherDeathDate', '2020-05-01')
            ->set('beneficiarySiblingsCount', 2)
            ->set('beneficiarySiblingsNote', 'Sibling notes')
            ->set('beneficiaryFinancialStatus', 'moderate')
            ->set('beneficiaryFinancialNotes', 'Financial notes')
            ->set('beneficiaryAddressText', 'Detailed address')
            ->set('beneficiaryArea', 'New Cairo')
            ->set('beneficiaryGovernorate', 'Cairo')
            ->set('beneficiaryGoogleMapsUrl', 'https://maps.google.com/?q=Cairo')
            ->set('beneficiaryDisabilityType', 'Physical')
            ->set('beneficiaryDisabilityDegree', 'mild')
            ->set('beneficiaryDoctorName', 'Doctor Name')
            ->set('beneficiaryHospitalName', 'Hospital Name')
            ->set('beneficiaryLastMedicalUpdate', now()->subDay()->toDateString())
            ->set('beneficiaryHealthStatus', 'Stable health')
            ->set('beneficiaryMedicalNotes', 'Medical notes')
            ->call('saveBeneficiary')
            ->assertDispatched('toast')
            ->assertSet('showBeneficiaryForm', false);

        $this->assertDatabaseHas('beneficiaries', [
            'full_name' => 'Complete Beneficiary',
            'service_group_id' => $group->id,
            'assigned_servant_id' => $servant->id,
            'facebook_url' => 'https://facebook.com/example',
            'instagram_url' => 'https://instagram.com/example',
            'guardian_relation' => 'Father',
            'father_status' => 'alive',
            'mother_status' => 'deceased',
            'siblings_count' => 2,
            'financial_status' => 'moderate',
            'area' => 'New Cairo',
            'governorate' => 'Cairo',
            'google_maps_url' => 'https://maps.google.com/?q=Cairo',
            'disability_type' => 'Physical',
            'disability_degree' => 'mild',
            'doctor_name' => 'Doctor Name',
            'hospital_name' => 'Hospital Name',
            'health_status' => 'Stable health',
            'medical_notes' => 'Medical notes',
        ]);

        $saved = Beneficiary::where('full_name', 'Complete Beneficiary')->firstOrFail();
        $this->assertNotNull($saved->photo);
        Storage::disk('public')->assertExists($saved->photo);
    }

    #[Test]
    public function beneficiary_mobile_cards_render_daily_actions(): void
    {
        $group = ServiceGroup::factory()->create();
        $familyLeader = $this->createFamilyLeader($group);
        $beneficiary = Beneficiary::factory()->create(['service_group_id' => $group->id]);

        Livewire::actingAs($familyLeader)
            ->test(BeneficiariesPage::class)
            ->assertSee('app-mobile-actions', false)
            ->assertSee('openBeneficiaryForm(' . $beneficiary->id . ')', false)
            ->assertSee('openVisitForm(' . $beneficiary->id . ')', false)
            ->assertSee('openPrayerForm(' . $beneficiary->id . ')', false)
            ->assertSee('openMedicalFileForm(' . $beneficiary->id . ')', false);
    }

    #[Test]
    public function beneficiary_assigned_servant_must_match_service_group_from_web_app(): void
    {
        $group = ServiceGroup::factory()->create();
        $otherGroup = ServiceGroup::factory()->create();
        $familyLeader = $this->createFamilyLeader($group);
        $foreignServant = $this->createServant($otherGroup);

        Livewire::actingAs($familyLeader)
            ->test(BeneficiariesPage::class)
            ->call('openBeneficiaryForm')
            ->set('beneficiaryFullName', 'Invalid Assignment')
            ->set('beneficiaryBirthDate', now()->subYears(10)->toDateString())
            ->set('beneficiaryGender', 'female')
            ->set('beneficiaryAssignedServantId', $foreignServant->id)
            ->call('saveBeneficiary')
            ->assertHasErrors('beneficiaryAssignedServantId');

        $this->assertDatabaseMissing('beneficiaries', [
            'full_name' => 'Invalid Assignment',
        ]);
    }

    #[Test]
    public function family_leader_can_update_beneficiary_from_web_app(): void
    {
        $group = ServiceGroup::factory()->create();
        $familyLeader = $this->createFamilyLeader($group);
        $beneficiary = Beneficiary::factory()->create([
            'service_group_id' => $group->id,
            'full_name' => 'Old Name',
            'status' => 'active',
        ]);

        Livewire::actingAs($familyLeader)
            ->test(BeneficiariesPage::class)
            ->call('openBeneficiaryForm', $beneficiary->id)
            ->set('beneficiaryFullName', 'Updated Name')
            ->set('beneficiaryBirthDate', now()->subYears(11)->toDateString())
            ->set('beneficiaryGender', 'female')
            ->set('beneficiaryRecordStatus', 'inactive')
            ->call('saveBeneficiary')
            ->assertDispatched('toast')
            ->assertSet('showBeneficiaryForm', false);

        $this->assertDatabaseHas('beneficiaries', [
            'id' => $beneficiary->id,
            'full_name' => 'Updated Name',
            'status' => 'inactive',
        ]);
    }

    #[Test]
    public function family_leader_can_upload_medical_file_from_web_app(): void
    {
        Storage::fake('private');

        $group = ServiceGroup::factory()->create();
        $familyLeader = $this->createFamilyLeader($group);
        $beneficiary = Beneficiary::factory()->create([
            'service_group_id' => $group->id,
        ]);
        $file = UploadedFile::fake()->create('medical-report.pdf', 128, 'application/pdf');

        Livewire::actingAs($familyLeader)
            ->test(MedicalFilesPage::class)
            ->call('openMedicalFileForm', $beneficiary->id)
            ->set('medicalFileTitle', 'Medical Report')
            ->set('medicalFileType', 'report')
            ->set('medicalUploadedFile', $file)
            ->call('saveMedicalFile')
            ->assertDispatched('toast')
            ->assertSet('showMedicalFileForm', false);

        $medicalFile = \App\Models\MedicalFile::query()
            ->where('beneficiary_id', $beneficiary->id)
            ->where('title', 'Medical Report')
            ->firstOrFail();

        $this->assertSame($familyLeader->id, $medicalFile->uploaded_by);
        Storage::disk('private')->assertExists($medicalFile->file_path);
    }

    #[Test]
    public function dedicated_medical_files_page_can_upload_medical_file(): void
    {
        Storage::fake('private');

        $group = ServiceGroup::factory()->create();
        $familyLeader = $this->createFamilyLeader($group);
        $beneficiary = Beneficiary::factory()->create([
            'service_group_id' => $group->id,
        ]);
        $file = UploadedFile::fake()->create('dedicated-medical-report.pdf', 64, 'application/pdf');

        Livewire::actingAs($familyLeader)
            ->test(MedicalFilesPage::class)
            ->call('openMedicalFileForm', $beneficiary->id)
            ->set('medicalFileTitle', 'Dedicated Medical Report')
            ->set('medicalFileType', 'report')
            ->set('medicalUploadedFile', $file)
            ->call('saveMedicalFile')
            ->assertDispatched('toast')
            ->assertSet('showMedicalFileForm', false);

        $medicalFile = \App\Models\MedicalFile::query()
            ->where('beneficiary_id', $beneficiary->id)
            ->where('title', 'Dedicated Medical Report')
            ->firstOrFail();

        $this->assertSame($familyLeader->id, $medicalFile->uploaded_by);
        Storage::disk('private')->assertExists($medicalFile->file_path);
    }

    #[Test]
    public function servant_cannot_open_medical_file_upload_form_from_web_app(): void
    {
        $group = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        Livewire::actingAs($servant)
            ->test(MedicalFilesPage::class)
            ->call('openMedicalFileForm')
            ->assertForbidden();
    }

    #[Test]
    public function medical_file_upload_beneficiary_must_be_in_scope_from_web_app(): void
    {
        Storage::fake('private');

        $group = ServiceGroup::factory()->create();
        $otherGroup = ServiceGroup::factory()->create();
        $familyLeader = $this->createFamilyLeader($group);
        $beneficiary = Beneficiary::factory()->create([
            'service_group_id' => $otherGroup->id,
        ]);

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($familyLeader)
            ->test(MedicalFilesPage::class)
            ->set('medicalFileBeneficiaryId', $beneficiary->id)
            ->set('medicalFileTitle', 'Out of scope')
            ->set('medicalFileType', 'report')
            ->set('medicalUploadedFile', UploadedFile::fake()->create('report.pdf', 64, 'application/pdf'))
            ->call('saveMedicalFile');
    }

    #[Test]
    public function family_leader_can_create_scheduled_visit_from_web_app(): void
    {
        $group = ServiceGroup::factory()->create();
        $familyLeader = $this->createFamilyLeader($group);
        $servant = $this->createServant($group);
        $secondServant = $this->createServant($group);
        $beneficiary = Beneficiary::factory()->create([
            'service_group_id' => $group->id,
        ]);

        Livewire::actingAs($familyLeader)
            ->test(ScheduledVisitsPage::class)
            ->call('openScheduledVisitForm', $beneficiary->id)
            ->set('scheduledVisitAssignedServantIds', [$servant->id, $secondServant->id])
            ->set('scheduledVisitDate', now()->addDay()->toDateString())
            ->set('scheduledVisitTime', '18:30')
            ->set('scheduledVisitNotes', 'تنسيق لزيارة مسائية')
            ->call('saveScheduledVisit')
            ->assertDispatched('toast')
            ->assertSet('showScheduledVisitForm', false);

        $this->assertDatabaseHas('scheduled_visits', [
            'beneficiary_id' => $beneficiary->id,
            'assigned_servant_id' => $servant->id,
            'status' => 'pending',
            'created_by' => $familyLeader->id,
        ]);

        $scheduledVisit = ScheduledVisit::query()
            ->where('beneficiary_id', $beneficiary->id)
            ->firstOrFail();

        $this->assertDatabaseHas('scheduled_visit_servants', [
            'scheduled_visit_id' => $scheduledVisit->id,
            'servant_id' => $servant->id,
        ]);
        $this->assertDatabaseHas('scheduled_visit_servants', [
            'scheduled_visit_id' => $scheduledVisit->id,
            'servant_id' => $secondServant->id,
        ]);
    }

    #[Test]
    public function dedicated_scheduled_visits_page_can_create_multi_servant_scheduled_visit(): void
    {
        $group = ServiceGroup::factory()->create();
        $familyLeader = $this->createFamilyLeader($group);
        $servant = $this->createServant($group);
        $secondServant = $this->createServant($group);
        $beneficiary = Beneficiary::factory()->create([
            'service_group_id' => $group->id,
        ]);

        Livewire::actingAs($familyLeader)
            ->test(ScheduledVisitsPage::class)
            ->call('openScheduledVisitForm', $beneficiary->id)
            ->set('scheduledVisitAssignedServantIds', [$servant->id, $secondServant->id])
            ->set('scheduledVisitDate', now()->addDays(2)->toDateString())
            ->set('scheduledVisitTime', '19:15')
            ->set('scheduledVisitNotes', 'Created from dedicated scheduled visits page')
            ->call('saveScheduledVisit')
            ->assertDispatched('toast')
            ->assertSet('showScheduledVisitForm', false);

        $scheduledVisit = ScheduledVisit::query()
            ->where('beneficiary_id', $beneficiary->id)
            ->where('notes', 'Created from dedicated scheduled visits page')
            ->firstOrFail();

        $this->assertEqualsCanonicalizing(
            [$servant->id, $secondServant->id],
            $scheduledVisit->servants()->pluck('users.id')->all(),
        );
    }

    #[Test]
    public function servant_can_cancel_their_scheduled_visit_from_web_app(): void
    {
        $group = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $secondServant = $this->createServant($group);
        $beneficiary = Beneficiary::factory()->create([
            'service_group_id' => $group->id,
        ]);
        $scheduledVisit = ScheduledVisit::factory()->create([
            'beneficiary_id' => $beneficiary->id,
            'assigned_servant_id' => $servant->id,
            'status' => 'pending',
        ]);
        $scheduledVisit->syncAssignedServants([$servant->id, $secondServant->id]);

        Livewire::actingAs($servant)
            ->test(ScheduledVisitsPage::class)
            ->call('cancelScheduledVisit', $scheduledVisit->id)
            ->assertDispatched('toast');

        $this->assertDatabaseHas('scheduled_visits', [
            'id' => $scheduledVisit->id,
            'status' => 'cancelled',
        ]);
    }

    #[Test]
    public function family_leader_can_update_scheduled_visit_assignees_from_web_app(): void
    {
        $group = ServiceGroup::factory()->create();
        $familyLeader = $this->createFamilyLeader($group);
        $firstServant = $this->createServant($group);
        $secondServant = $this->createServant($group);
        $beneficiary = Beneficiary::factory()->create([
            'service_group_id' => $group->id,
        ]);
        $scheduledVisit = ScheduledVisit::factory()->create([
            'beneficiary_id' => $beneficiary->id,
            'assigned_servant_id' => $firstServant->id,
            'status' => 'pending',
            'scheduled_date' => now()->addDay()->toDateString(),
        ]);
        $scheduledVisit->syncAssignedServants([$firstServant->id]);

        Livewire::actingAs($familyLeader)
            ->test(ScheduledVisitsPage::class)
            ->call('editScheduledVisit', $scheduledVisit->id)
            ->assertSet('editingScheduledVisitId', $scheduledVisit->id)
            ->set('scheduledVisitAssignedServantIds', [$secondServant->id])
            ->set('scheduledVisitDate', now()->addDays(2)->toDateString())
            ->set('scheduledVisitTime', '19:15')
            ->call('saveScheduledVisit')
            ->assertDispatched('toast')
            ->assertSet('showScheduledVisitForm', false);

        $this->assertDatabaseHas('scheduled_visits', [
            'id' => $scheduledVisit->id,
            'assigned_servant_id' => $secondServant->id,
            'scheduled_time' => '19:15',
        ]);
        $this->assertDatabaseMissing('scheduled_visit_servants', [
            'scheduled_visit_id' => $scheduledVisit->id,
            'servant_id' => $firstServant->id,
        ]);
        $this->assertDatabaseHas('scheduled_visit_servants', [
            'scheduled_visit_id' => $scheduledVisit->id,
            'servant_id' => $secondServant->id,
        ]);
    }

    #[Test]
    public function scheduled_visit_assignees_must_match_beneficiary_group_from_web_app(): void
    {
        $group = ServiceGroup::factory()->create();
        $otherGroup = ServiceGroup::factory()->create();
        $admin = $this->createSuperAdmin();
        $foreignServant = $this->createServant($otherGroup);
        $beneficiary = Beneficiary::factory()->create([
            'service_group_id' => $group->id,
        ]);

        Livewire::actingAs($admin)
            ->test(ScheduledVisitsPage::class)
            ->call('openScheduledVisitForm', $beneficiary->id)
            ->set('scheduledVisitAssignedServantIds', [$foreignServant->id])
            ->set('scheduledVisitDate', now()->addDay()->toDateString())
            ->set('scheduledVisitTime', '18:30')
            ->call('saveScheduledVisit')
            ->assertHasErrors('scheduledVisitAssignedServantIds');

        $this->assertDatabaseMissing('scheduled_visits', [
            'beneficiary_id' => $beneficiary->id,
            'assigned_servant_id' => $foreignServant->id,
        ]);
    }

    #[Test]
    public function recording_visit_from_scheduled_visit_marks_it_completed(): void
    {
        $group = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $secondServant = $this->createServant($group);
        $beneficiary = Beneficiary::factory()->create([
            'assigned_servant_id' => $servant->id,
            'service_group_id' => $group->id,
        ]);
        $scheduledVisit = ScheduledVisit::factory()->create([
            'beneficiary_id' => $beneficiary->id,
            'assigned_servant_id' => $servant->id,
            'status' => 'pending',
            'scheduled_date' => now()->toDateString(),
        ]);
        $scheduledVisit->syncAssignedServants([$servant->id, $secondServant->id]);

        Livewire::actingAs($servant)
            ->test(ScheduledVisitsPage::class)
            ->call('openVisitFromScheduled', $scheduledVisit->id)
            ->set('visitType', 'home_visit')
            ->set('beneficiaryStatus', 'good')
            ->call('saveVisit');

        $scheduledVisit->refresh();

        $this->assertSame('completed', $scheduledVisit->status);
        $this->assertNotNull($scheduledVisit->completed_visit_id);
    }

    #[Test]
    public function completed_scheduled_visit_cannot_be_cancelled_or_recorded_again_from_web_app(): void
    {
        $group = ServiceGroup::factory()->create();
        $familyLeader = $this->createFamilyLeader($group);
        $beneficiary = Beneficiary::factory()->create([
            'service_group_id' => $group->id,
        ]);
        $scheduledVisit = ScheduledVisit::factory()->create([
            'beneficiary_id' => $beneficiary->id,
            'status' => 'completed',
        ]);

        Livewire::actingAs($familyLeader)
            ->test(ScheduledVisitsPage::class)
            ->call('cancelScheduledVisit', $scheduledVisit->id)
            ->assertForbidden();

        Livewire::actingAs($familyLeader)
            ->test(ScheduledVisitsPage::class)
            ->call('openVisitFromScheduled', $scheduledVisit->id)
            ->assertForbidden();

        $this->assertDatabaseHas('scheduled_visits', [
            'id' => $scheduledVisit->id,
            'status' => 'completed',
        ]);
    }

    #[Test]
    public function cancelled_scheduled_visit_cannot_be_updated_from_web_app(): void
    {
        $group = ServiceGroup::factory()->create();
        $familyLeader = $this->createFamilyLeader($group);
        $servant = $this->createServant($group);
        $beneficiary = Beneficiary::factory()->create([
            'service_group_id' => $group->id,
        ]);
        $scheduledVisit = ScheduledVisit::factory()->create([
            'beneficiary_id' => $beneficiary->id,
            'assigned_servant_id' => $servant->id,
            'status' => 'cancelled',
            'scheduled_date' => now()->addDay()->toDateString(),
        ]);

        Livewire::actingAs($familyLeader)
            ->test(ScheduledVisitsPage::class)
            ->set('editingScheduledVisitId', $scheduledVisit->id)
            ->set('scheduledVisitBeneficiaryId', $beneficiary->id)
            ->set('scheduledVisitAssignedServantIds', [$servant->id])
            ->set('scheduledVisitDate', now()->addDays(2)->toDateString())
            ->set('scheduledVisitTime', '18:30')
            ->call('saveScheduledVisit')
            ->assertForbidden();

        $this->assertDatabaseHas('scheduled_visits', [
            'id' => $scheduledVisit->id,
            'status' => 'cancelled',
        ]);
    }

    #[Test]
    public function service_leader_can_create_servant_from_web_app_in_managed_group(): void
    {
        $serviceLeader = $this->createServiceLeader();
        $group = ServiceGroup::factory()->create([
            'service_leader_id' => $serviceLeader->id,
        ]);

        Livewire::actingAs($serviceLeader)
            ->test(UsersPage::class)
            ->call('openUserForm')
            ->set('userName', 'New Servant')
            ->set('userEmail', 'new-servant@example.com')
            ->set('userPhone', '01000000000')
            ->set('userPassword', 'password123')
            ->set('userRole', UserRole::Servant->value)
            ->set('userServiceGroupId', $group->id)
            ->set('userLocale', 'ar')
            ->call('saveUser')
            ->assertDispatched('toast')
            ->assertSet('showUserForm', false);

        $this->assertDatabaseHas('users', [
            'name' => 'New Servant',
            'email' => 'new-servant@example.com',
            'role' => UserRole::Servant->value,
            'service_group_id' => $group->id,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function dedicated_users_page_can_create_servant_in_managed_group(): void
    {
        $serviceLeader = $this->createServiceLeader();
        $group = ServiceGroup::factory()->create([
            'service_leader_id' => $serviceLeader->id,
        ]);

        Livewire::actingAs($serviceLeader)
            ->test(UsersPage::class)
            ->call('openUserForm')
            ->set('userName', 'Dedicated Servant')
            ->set('userEmail', 'dedicated-servant@example.com')
            ->set('userPassword', 'password123')
            ->set('userRole', UserRole::Servant->value)
            ->set('userServiceGroupId', $group->id)
            ->set('userLocale', 'ar')
            ->call('saveUser')
            ->assertDispatched('toast')
            ->assertSet('showUserForm', false);

        $this->assertDatabaseHas('users', [
            'name' => 'Dedicated Servant',
            'email' => 'dedicated-servant@example.com',
            'role' => UserRole::Servant->value,
            'service_group_id' => $group->id,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function service_leader_cannot_create_user_in_unmanaged_group_from_web_app(): void
    {
        $serviceLeader = $this->createServiceLeader();
        ServiceGroup::factory()->create([
            'service_leader_id' => $serviceLeader->id,
        ]);
        $otherGroup = ServiceGroup::factory()->create();

        Livewire::actingAs($serviceLeader)
            ->test(UsersPage::class)
            ->call('openUserForm')
            ->set('userName', 'Out Of Scope')
            ->set('userEmail', 'out-of-scope@example.com')
            ->set('userPassword', 'password123')
            ->set('userRole', UserRole::Servant->value)
            ->set('userServiceGroupId', $otherGroup->id)
            ->call('saveUser')
            ->assertHasErrors('userServiceGroupId');

        $this->assertDatabaseMissing('users', [
            'email' => 'out-of-scope@example.com',
        ]);
    }

    #[Test]
    public function super_admin_can_toggle_user_active_from_web_app(): void
    {
        $admin = $this->createSuperAdmin();
        $target = User::factory()->create([
            'role' => UserRole::ServiceLeader,
            'is_active' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(UsersPage::class)
            ->call('toggleUserActive', $target->id)
            ->assertDispatched('toast');

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'is_active' => false,
        ]);
    }

    #[Test]
    public function family_leader_cannot_open_user_create_form_from_web_app(): void
    {
        $group = ServiceGroup::factory()->create();
        $familyLeader = $this->createFamilyLeader($group);

        Livewire::actingAs($familyLeader)
            ->test(UsersPage::class)
            ->call('openUserForm')
            ->assertForbidden();
    }

    #[Test]
    public function service_leader_can_create_service_group_from_web_app_assigned_to_self(): void
    {
        $serviceLeader = $this->createServiceLeader();

        Livewire::actingAs($serviceLeader)
            ->test(ServiceGroupsPage::class)
            ->call('openServiceGroupForm')
            ->set('serviceGroupName', 'New Family Group')
            ->set('serviceGroupDescription', 'Created from web app')
            ->call('saveServiceGroup')
            ->assertDispatched('toast')
            ->assertSet('showServiceGroupForm', false);

        $this->assertDatabaseHas('service_groups', [
            'name' => 'New Family Group',
            'description' => 'Created from web app',
            'service_leader_id' => $serviceLeader->id,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function dedicated_service_groups_page_can_create_service_group_assigned_to_service_leader(): void
    {
        $serviceLeader = $this->createServiceLeader();

        Livewire::actingAs($serviceLeader)
            ->test(ServiceGroupsPage::class)
            ->call('openServiceGroupForm')
            ->set('serviceGroupName', 'Dedicated Family Group')
            ->set('serviceGroupDescription', 'Created from dedicated service groups page')
            ->call('saveServiceGroup')
            ->assertDispatched('toast')
            ->assertSet('showServiceGroupForm', false);

        $this->assertDatabaseHas('service_groups', [
            'name' => 'Dedicated Family Group',
            'description' => 'Created from dedicated service groups page',
            'service_leader_id' => $serviceLeader->id,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function service_leader_cannot_edit_unmanaged_service_group_from_web_app(): void
    {
        $serviceLeader = $this->createServiceLeader();
        $managedGroup = ServiceGroup::factory()->create([
            'service_leader_id' => $serviceLeader->id,
            'name' => 'Managed Group',
        ]);
        $otherLeader = $this->createServiceLeader();
        $foreignGroup = ServiceGroup::factory()->create([
            'service_leader_id' => $otherLeader->id,
            'name' => 'Foreign Group',
        ]);

        Livewire::actingAs($serviceLeader)
            ->test(ServiceGroupsPage::class)
            ->assertSee($managedGroup->name)
            ->assertDontSee($foreignGroup->name);

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($serviceLeader)
            ->test(ServiceGroupsPage::class)
            ->call('openServiceGroupForm', $foreignGroup->id);
    }

    #[Test]
    public function disabling_service_group_from_web_app_marks_beneficiaries_inactive(): void
    {
        $admin = $this->createSuperAdmin();
        $group = ServiceGroup::factory()->create(['is_active' => true]);
        $beneficiary = Beneficiary::factory()->create([
            'service_group_id' => $group->id,
            'status' => 'active',
        ]);

        Livewire::actingAs($admin)
            ->test(ServiceGroupsPage::class)
            ->call('toggleServiceGroupActive', $group->id)
            ->assertDispatched('toast');

        $this->assertDatabaseHas('service_groups', [
            'id' => $group->id,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('beneficiaries', [
            'id' => $beneficiary->id,
            'status' => 'inactive',
        ]);
    }
}
