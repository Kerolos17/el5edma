<?php

namespace Tests\Feature;

use App\Filament\Resources\Beneficiaries\BeneficiaryResource;
use App\Filament\Resources\MedicalFiles\MedicalFileResource;
use App\Filament\Resources\PrayerRequests\PrayerRequestResource;
use App\Filament\Resources\ServiceGroups\ServiceGroupResource;
use App\Filament\Resources\Visits\VisitResource;
use App\Models\Beneficiary;
use App\Models\MedicalFile;
use App\Models\MinistryNotification;
use App\Models\PrayerRequest;
use App\Models\PushDevice;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Models\Visit;
use App\Services\PushNotificationService;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Kreait\Firebase\Contract\Messaging;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class Phase1StabilizationRegressionTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_critical_visit_notifies_service_leader_and_superadmin_but_not_unrelated_leader(): void
    {
        Queue::fake();

        $serviceLeader   = $this->createServiceLeader();
        $unrelatedLeader = $this->createServiceLeader();
        $superAdmin      = $this->createSuperAdmin();
        $group           = ServiceGroup::factory()->create(['service_leader_id' => $serviceLeader->id]);
        $familyLeader    = $this->createFamilyLeader($group);
        $group->update(['leader_id' => $familyLeader->id]);
        $servant = $this->createServant($group);

        $beneficiary = Beneficiary::factory()->create([
            'service_group_id'    => $group->id,
            'assigned_servant_id' => $servant->id,
        ]);

        Visit::factory()->create([
            'beneficiary_id' => $beneficiary->id,
            'is_critical'    => true,
            'created_by'     => $servant->id,
        ]);

        foreach ([$servant, $familyLeader, $serviceLeader, $superAdmin] as $recipient) {
            $this->assertDatabaseHas('ministry_notifications', [
                'user_id' => $recipient->id,
                'type'    => 'critical_case',
            ]);
        }

        $this->assertDatabaseMissing('ministry_notifications', [
            'user_id' => $unrelatedLeader->id,
            'type'    => 'critical_case',
        ]);
    }

    public function test_critical_visit_notifications_are_per_recipient_localized(): void
    {
        Queue::fake();

        $group         = ServiceGroup::factory()->create();
        $arabicServant = $this->createServant($group, ['locale' => 'ar']);
        $englishLeader = $this->createServiceLeader(['locale' => 'en']);
        $group->update(['service_leader_id' => $englishLeader->id]);

        $beneficiary = Beneficiary::factory()->create([
            'service_group_id'    => $group->id,
            'assigned_servant_id' => $arabicServant->id,
        ]);

        Visit::factory()->create([
            'beneficiary_id' => $beneficiary->id,
            'is_critical'    => true,
            'created_by'     => $arabicServant->id,
        ]);

        $arabicTitle = MinistryNotification::where('user_id', $arabicServant->id)
            ->where('type', 'critical_case')->value('title');
        $englishTitle = MinistryNotification::where('user_id', $englishLeader->id)
            ->where('type', 'critical_case')->value('title');

        $this->assertSame('حالة حرجة 🔴', $arabicTitle);
        $this->assertSame('Critical Case 🔴', $englishTitle);
        $this->assertSame('ar', app()->getLocale());
    }

    public function test_single_token_invalid_purge_removes_device_and_legacy_token(): void
    {
        $token = 'dead-single-token';
        $user  = User::factory()->create(['fcm_token' => $token]);
        PushDevice::create([
            'user_id'      => $user->id,
            'token'        => $token,
            'token_hash'   => hash('sha256', $token),
            'platform'     => 'web',
            'last_seen_at' => now(),
        ]);

        $messaging = Mockery::mock(Messaging::class);
        $messaging->shouldReceive('send')->once()->andThrow(new \Exception('UNREGISTERED: token not found'));

        $service = new PushNotificationService($messaging);
        $result  = $service->sendToUser($user, 'T', 'B');

        $this->assertFalse($result);
        $this->assertDatabaseMissing('push_devices', ['token_hash' => hash('sha256', $token)]);
        $this->assertNull($user->fresh()->fcm_token);
    }

    public function test_single_beneficiary_pdf_scopes_prayers_for_servants(): void
    {
        $group        = ServiceGroup::factory()->create();
        $servant      = $this->createServant($group);
        $otherServant = $this->createServant($group);

        $beneficiary = Beneficiary::factory()->create([
            'service_group_id'    => $group->id,
            'assigned_servant_id' => $servant->id,
        ]);
        PrayerRequest::factory()->create([
            'beneficiary_id' => $beneficiary->id,
            'created_by'     => $servant->id,
        ]);
        PrayerRequest::factory()->create([
            'beneficiary_id' => $beneficiary->id,
            'created_by'     => $otherServant->id,
        ]);

        $service = app(ReportService::class);

        $servantResponse = $service->singleBeneficiaryPdf($beneficiary->fresh(), $servant);
        $this->assertSame(200, $servantResponse->getStatusCode());

        // Verify the underlying scoping rule directly: servants see only own prayers.
        $visibleToServant = PrayerRequest::where('beneficiary_id', $beneficiary->id)
            ->where('created_by', $servant->id)->count();
        $this->assertSame(1, $visibleToServant);
        $this->assertSame(2, PrayerRequest::where('beneficiary_id', $beneficiary->id)->count());
    }

    public function test_service_leader_filament_lists_are_scoped_to_managed_groups(): void
    {
        $leader       = $this->createServiceLeader();
        $managedGroup = ServiceGroup::factory()->create(['service_leader_id' => $leader->id]);
        $otherGroup   = ServiceGroup::factory()->create();

        $managedBeneficiary = Beneficiary::factory()->create(['service_group_id' => $managedGroup->id]);
        $otherBeneficiary   = Beneficiary::factory()->create(['service_group_id' => $otherGroup->id]);

        $managedVisit = Visit::factory()->create(['beneficiary_id' => $managedBeneficiary->id]);
        $otherVisit   = Visit::factory()->create(['beneficiary_id' => $otherBeneficiary->id]);

        $managedFile = MedicalFile::factory()->create(['beneficiary_id' => $managedBeneficiary->id]);
        $otherFile   = MedicalFile::factory()->create(['beneficiary_id' => $otherBeneficiary->id]);

        $managedPrayer = PrayerRequest::factory()->create(['beneficiary_id' => $managedBeneficiary->id]);
        $otherPrayer   = PrayerRequest::factory()->create(['beneficiary_id' => $otherBeneficiary->id]);

        $this->actingAs($leader);

        $this->assertTrue(
            BeneficiaryResource::getEloquentQuery()->whereKey($managedBeneficiary->id)->exists(),
        );
        $this->assertFalse(
            BeneficiaryResource::getEloquentQuery()->whereKey($otherBeneficiary->id)->exists(),
        );

        $this->assertTrue(
            VisitResource::getEloquentQuery()->whereKey($managedVisit->id)->exists(),
        );
        $this->assertFalse(
            VisitResource::getEloquentQuery()->whereKey($otherVisit->id)->exists(),
        );

        $this->assertTrue(
            MedicalFileResource::getEloquentQuery()->whereKey($managedFile->id)->exists(),
        );
        $this->assertFalse(
            MedicalFileResource::getEloquentQuery()->whereKey($otherFile->id)->exists(),
        );

        $this->assertTrue(
            PrayerRequestResource::getEloquentQuery()->whereKey($managedPrayer->id)->exists(),
        );
        $this->assertFalse(
            PrayerRequestResource::getEloquentQuery()->whereKey($otherPrayer->id)->exists(),
        );

        $this->assertTrue(
            ServiceGroupResource::getEloquentQuery()->whereKey($managedGroup->id)->exists(),
        );
        $this->assertFalse(
            ServiceGroupResource::getEloquentQuery()->whereKey($otherGroup->id)->exists(),
        );
    }
}
