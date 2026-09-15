<?php

namespace Tests\Feature;

use App\Console\Commands\SendBirthdayReminders;
use App\Console\Commands\SendScheduledVisitReminders;
use App\Console\Commands\SendUnvisitedAlerts;
use App\Enums\UserRole;
use App\Exports\BeneficiariesExport;
use App\Exports\VisitsExport;
use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Models\Visit;
use App\Observers\BeneficiaryObserver;
use App\Observers\UserObserver;
use App\Observers\VisitObserver;
use App\Services\ReportService;
use App\Support\WebAppScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Preservation coverage for the current application architecture.
 *
 * Filament `/admin` is an internal SuperAdmin-only fallback. The primary
 * application for servants and leaders is the authenticated `/app` surface.
 */
class PreservationTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $serviceLeader;

    private User $familyLeader;

    private User $servant1;

    private User $servant2;

    private ServiceGroup $serviceGroup1;

    private ServiceGroup $serviceGroup2;

    private Beneficiary $beneficiary1;

    private Beneficiary $beneficiary2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serviceGroup1 = ServiceGroup::factory()->create([
            'name'      => 'Group 1',
            'is_active' => true,
        ]);
        $this->serviceGroup2 = ServiceGroup::factory()->create([
            'name'      => 'Group 2',
            'is_active' => true,
        ]);

        $this->superAdmin = User::factory()->create([
            'role'      => UserRole::SuperAdmin,
            'locale'    => 'ar',
            'is_active' => true,
        ]);
        $this->serviceLeader = User::factory()->create([
            'role'      => UserRole::ServiceLeader,
            'locale'    => 'ar',
            'is_active' => true,
        ]);
        $this->familyLeader = User::factory()->create([
            'role'             => UserRole::FamilyLeader,
            'service_group_id' => $this->serviceGroup1->id,
            'locale'           => 'ar',
            'is_active'        => true,
        ]);
        $this->servant1 = User::factory()->create([
            'role'             => UserRole::Servant,
            'service_group_id' => $this->serviceGroup1->id,
            'locale'           => 'ar',
            'is_active'        => true,
        ]);
        $this->servant2 = User::factory()->create([
            'role'             => UserRole::Servant,
            'service_group_id' => $this->serviceGroup2->id,
            'locale'           => 'en',
            'is_active'        => true,
        ]);

        $this->serviceGroup1->update([
            'leader_id'         => $this->familyLeader->id,
            'service_leader_id' => $this->serviceLeader->id,
        ]);

        $this->beneficiary1 = Beneficiary::factory()->create([
            'service_group_id'    => $this->serviceGroup1->id,
            'assigned_servant_id' => $this->servant1->id,
            'status'              => 'active',
            'full_name'           => 'Test Beneficiary 1',
        ]);
        $this->beneficiary2 = Beneficiary::factory()->create([
            'service_group_id'    => $this->serviceGroup2->id,
            'assigned_servant_id' => $this->servant2->id,
            'status'              => 'active',
            'full_name'           => 'Test Beneficiary 2',
        ]);

        Visit::factory()->create([
            'beneficiary_id'     => $this->beneficiary1->id,
            'visit_date'         => now()->subDays(5),
            'created_by'         => $this->servant1->id,
            'type'               => 'home_visit',
            'beneficiary_status' => 'good',
        ]);
        Visit::factory()->create([
            'beneficiary_id'     => $this->beneficiary2->id,
            'visit_date'         => now()->subDays(10),
            'created_by'         => $this->servant2->id,
            'type'               => 'home_visit',
            'beneficiary_status' => 'good',
        ]);
    }

    public function test_preserve_login_and_primary_app_access(): void
    {
        Auth::login($this->servant1);
        $this->assertAuthenticatedAs($this->servant1);

        $this->get('/app/dashboard')->assertOk();

        Auth::logout();
        $this->assertGuest();
    }

    public function test_preserve_filament_as_super_admin_only_fallback(): void
    {
        $this->actingAs($this->servant1);
        $this->get('/admin')->assertForbidden();

        $this->actingAs($this->superAdmin);
        $this->get('/admin')->assertSuccessful();
    }

    public function test_preserve_beneficiary_list_display_and_scoping(): void
    {
        $this->actingAs($this->servant1);

        $response = $this->get('/app/beneficiaries');
        $response->assertOk();
        $response->assertSee($this->beneficiary1->full_name);
        $response->assertDontSee($this->beneficiary2->full_name);

        $this->actingAs($this->superAdmin);

        $response = $this->get('/app/beneficiaries');
        $response->assertOk();
        $response->assertSee($this->beneficiary1->full_name);
        $response->assertSee($this->beneficiary2->full_name);
    }

    public function test_preserve_authorized_visit_creation(): void
    {
        $this->actingAs($this->servant1);

        $visit = Visit::create([
            'beneficiary_id'     => $this->beneficiary1->id,
            'visit_date'         => now(),
            'type'               => 'home_visit',
            'duration_minutes'   => 60,
            'beneficiary_status' => 'good',
            'feedback'           => 'Test visit feedback',
            'is_critical'        => false,
            'created_by'         => $this->servant1->id,
        ]);

        $this->assertDatabaseHas('visits', [
            'id'             => $visit->id,
            'beneficiary_id' => $this->beneficiary1->id,
            'created_by'     => $this->servant1->id,
            'type'           => 'home_visit',
        ]);
        $this->assertFalse($visit->is_critical);
    }

    public function test_preserve_report_generation_surface_and_routes(): void
    {
        $this->actingAs($this->serviceLeader);

        $this->get('/app/reports')->assertOk();

        $this->assertStringContainsString(
            '/reports/beneficiaries-pdf',
            route('reports.beneficiaries.pdf'),
        );
        $this->assertStringContainsString(
            '/reports/visits-pdf',
            route('reports.visits.pdf'),
        );

        $reportService = new ReportService;
        $this->assertTrue(method_exists($reportService, 'beneficiariesPdf'));
        $this->assertTrue(method_exists($reportService, 'visitsPdf'));
        $this->assertTrue(method_exists($reportService, 'unvisitedPdf'));
    }

    public function test_preserve_language_switching(): void
    {
        $this->actingAs($this->servant1);

        $this->post('/language/en')->assertRedirect();
        $this->servant1->refresh();
        $this->assertSame('en', $this->servant1->locale);
        $this->assertSame('en', session('locale'));

        $this->post('/language/ar')->assertRedirect();
        $this->servant1->refresh();
        $this->assertSame('ar', $this->servant1->locale);
        $this->assertSame('ar', session('locale'));
    }

    public function test_preserve_role_based_data_access(): void
    {
        $servantBeneficiaries = WebAppScope::beneficiaries($this->servant1)->get();
        $this->assertCount(1, $servantBeneficiaries);
        $this->assertSame($this->beneficiary1->id, $servantBeneficiaries->first()->id);

        $familyLeaderBeneficiaries = WebAppScope::beneficiaries($this->familyLeader)->get();
        $this->assertCount(1, $familyLeaderBeneficiaries);
        $this->assertSame($this->beneficiary1->id, $familyLeaderBeneficiaries->first()->id);

        $serviceLeaderBeneficiaries = WebAppScope::beneficiaries($this->serviceLeader)->get();
        $this->assertCount(1, $serviceLeaderBeneficiaries);
        $this->assertSame($this->beneficiary1->id, $serviceLeaderBeneficiaries->first()->id);

        $this->assertCount(2, WebAppScope::beneficiaries($this->superAdmin)->get());
    }

    public function test_preserve_notification_system_contracts(): void
    {
        $this->assertTrue(method_exists(User::class, 'ministryNotifications'));
        $this->assertTrue(method_exists(User::class, 'pushDevices'));
        $this->assertTrue(method_exists(User::class, 'pushTokens'));
        $this->assertTrue(class_exists(SendUnvisitedAlerts::class));
        $this->assertTrue(class_exists(SendBirthdayReminders::class));
        $this->assertTrue(class_exists(SendScheduledVisitReminders::class));
    }

    public function test_preserve_audit_logging(): void
    {
        $this->actingAs($this->familyLeader);

        $this->assertTrue(class_exists(BeneficiaryObserver::class));
        $this->assertTrue(class_exists(VisitObserver::class));
        $this->assertTrue(class_exists(UserObserver::class));
        $this->assertTrue(class_exists(AuditLog::class));

        $beneficiary = Beneficiary::factory()->create([
            'service_group_id' => $this->serviceGroup1->id,
            'full_name'        => 'Audit Test Beneficiary',
            'status'           => 'active',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'model_type' => Beneficiary::class,
            'model_id'   => $beneficiary->id,
            'action'     => 'created',
        ]);

        $beneficiary->update(['status' => 'inactive']);

        $this->assertDatabaseHas('audit_logs', [
            'model_type' => Beneficiary::class,
            'model_id'   => $beneficiary->id,
            'action'     => 'updated',
        ]);
    }

    public function test_preserve_data_export_functionality(): void
    {
        $beneficiariesExport = new BeneficiariesExport($this->serviceLeader);
        $query               = $beneficiariesExport->query();

        $this->assertInstanceOf(Builder::class, $query);
        $this->assertNotEmpty($beneficiariesExport->headings());
        $this->assertTrue(class_exists(VisitsExport::class));
    }

    public function test_preserve_search_and_filtering_scope(): void
    {
        $activeBeneficiaries = WebAppScope::beneficiaries($this->servant1)
            ->where('status', 'active')
            ->where(function (Builder $query): void {
                $query->where('full_name', 'like', '%Test Beneficiary%')
                    ->orWhere('phone', 'like', '%Test Beneficiary%');
            })
            ->get();

        $this->assertCount(1, $activeBeneficiaries);
        $this->assertSame($this->beneficiary1->id, $activeBeneficiaries->first()->id);
    }

    public function test_comprehensive_preservation_summary(): void
    {
        $this->assertTrue(class_exists(User::class));
        $this->assertTrue(class_exists(Beneficiary::class));
        $this->assertTrue(class_exists(Visit::class));
        $this->assertTrue(class_exists(ServiceGroup::class));

        $this->assertTrue(method_exists(User::class, 'serviceGroup'));
        $this->assertTrue(method_exists(User::class, 'assignedBeneficiaries'));
        $this->assertTrue(method_exists(Beneficiary::class, 'serviceGroup'));
        $this->assertTrue(method_exists(Beneficiary::class, 'visits'));
        $this->assertTrue(method_exists(Visit::class, 'beneficiary'));
        $this->assertTrue(method_exists(Visit::class, 'servants'));
    }
}
