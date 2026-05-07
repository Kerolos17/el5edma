<?php

declare(strict_types=1);

namespace Tests\Feature\WebApp;

use App\Enums\UserRole;
use App\Livewire\WebApp\BeneficiariesPage;
use App\Livewire\WebApp\MedicalFilesPage;
use App\Livewire\WebApp\PrayerRequestsPage;
use App\Livewire\WebApp\ReportsPage;
use App\Livewire\WebApp\ScheduledVisitsPage;
use App\Livewire\WebApp\ServiceGroupsPage;
use App\Livewire\WebApp\UsersPage;
use App\Livewire\WebApp\VisitsPage;
use App\Models\Beneficiary;
use App\Models\MedicalFile;
use App\Models\PrayerRequest;
use App\Models\ScheduledVisit;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class AppShellTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    #[Test]
    public function guest_is_redirected_from_app_dashboard(): void
    {
        $this->get(route('app.dashboard'))
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    #[Test]
    public function inactive_user_is_logged_out_from_app(): void
    {
        $group = ServiceGroup::factory()->create();
        $user = $this->createServant($group, ['is_active' => false]);

        $this->actingAs($user)
            ->get(route('app.dashboard'))
            ->assertRedirect(route('filament.admin.auth.login'));

        $this->assertGuest();
    }

    #[Test]
    public function active_roles_can_open_app_dashboard(): void
    {
        $group = ServiceGroup::factory()->create();

        foreach ([
            $this->createSuperAdmin(),
            $this->createServiceLeader(),
            $this->createFamilyLeader($group),
            $this->createServant($group),
        ] as $user) {
            $this->actingAs($user)
                ->get(route('app.dashboard'))
                ->assertOk()
                ->assertSee('Ministry System')
                ->assertSee('واجهة موحدة لكل الأدوار');
        }
    }

    #[Test]
    public function root_app_url_redirects_to_dashboard(): void
    {
        $group = ServiceGroup::factory()->create();
        $user = $this->createServant($group);

        $this->actingAs($user)
            ->get(route('app.home'))
            ->assertRedirect('/app/dashboard');
    }

    #[Test]
    public function servant_dashboard_is_scoped_to_their_service_group(): void
    {
        $group = ServiceGroup::factory()->create();
        $otherGroup = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        $inScope = Beneficiary::factory()->create([
            'service_group_id' => $group->id,
            'assigned_servant_id' => null,
        ]);
        $outOfScope = Beneficiary::factory()->create([
            'service_group_id' => $otherGroup->id,
            'assigned_servant_id' => null,
        ]);

        Visit::factory()->create([
            'beneficiary_id' => $inScope->id,
            'created_by' => $servant->id,
            'visit_date' => now(),
        ]);
        Visit::factory()->create([
            'beneficiary_id' => $outOfScope->id,
            'visit_date' => now(),
        ]);

        $this->actingAs($servant)
            ->get(route('app.dashboard'))
            ->assertOk()
            ->assertSee('>1</strong>', false)
            ->assertDontSee($outOfScope->full_name);
    }

    #[Test]
    public function servant_cannot_open_users_or_service_groups_pages(): void
    {
        $group = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        $this->actingAs($servant)
            ->get(route('app.users'))
            ->assertForbidden();

        $this->actingAs($servant)
            ->get(route('app.service-groups'))
            ->assertForbidden();
    }

    #[Test]
    public function family_leader_can_open_users_page_with_scoped_records(): void
    {
        $group = ServiceGroup::factory()->create(['name' => 'Alpha Group']);
        $otherGroup = ServiceGroup::factory()->create(['name' => 'Beta Group']);
        $familyLeader = $this->createFamilyLeader($group);

        $inScopeUser = User::factory()->create([
            'name' => 'Scoped User',
            'role' => UserRole::Servant,
            'service_group_id' => $group->id,
            'is_active' => true,
        ]);
        $outOfScopeUser = User::factory()->create([
            'name' => 'Hidden User',
            'role' => UserRole::Servant,
            'service_group_id' => $otherGroup->id,
            'is_active' => true,
        ]);

        $this->actingAs($familyLeader)
            ->get(route('app.users'))
            ->assertOk()
            ->assertSeeLivewire(UsersPage::class)
            ->assertSee('الخدام والمستخدمون')
            ->assertSee($inScopeUser->name)
            ->assertDontSee($outOfScopeUser->name);
    }

    #[Test]
    public function family_leader_can_open_service_groups_page_with_scoped_records(): void
    {
        $group = ServiceGroup::factory()->create(['name' => 'Family Group']);
        $otherGroup = ServiceGroup::factory()->create(['name' => 'Other Group']);
        $familyLeader = $this->createFamilyLeader($group);

        $this->actingAs($familyLeader)
            ->get(route('app.service-groups'))
            ->assertOk()
            ->assertSeeLivewire(ServiceGroupsPage::class)
            ->assertSee($group->name)
            ->assertDontSee($otherGroup->name);
    }

    #[Test]
    public function servant_can_open_core_resource_pages(): void
    {
        $group = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        foreach ([
            'app.beneficiaries',
            'app.visits',
            'app.scheduled-visits',
            'app.prayer-requests',
            'app.medical-files',
            'app.reports',
        ] as $route) {
            $this->actingAs($servant)
                ->get(route($route))
                ->assertOk();
        }
    }

    #[Test]
    public function beneficiaries_page_uses_dedicated_component_and_keeps_role_scope(): void
    {
        $group = ServiceGroup::factory()->create();
        $otherGroup = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $inScope = Beneficiary::factory()->create([
            'full_name' => 'Scoped Beneficiary',
            'service_group_id' => $group->id,
            'assigned_servant_id' => $servant->id,
        ]);
        $outOfScope = Beneficiary::factory()->create([
            'full_name' => 'Hidden Beneficiary',
            'service_group_id' => $otherGroup->id,
            'assigned_servant_id' => null,
        ]);

        $this->actingAs($servant)
            ->get(route('app.beneficiaries'))
            ->assertOk()
            ->assertSeeLivewire(BeneficiariesPage::class)
            ->assertSee($inScope->full_name)
            ->assertDontSee($outOfScope->full_name);
    }

    #[Test]
    public function visits_page_uses_dedicated_component_and_keeps_role_scope(): void
    {
        $group = ServiceGroup::factory()->create();
        $otherGroup = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $inScope = Beneficiary::factory()->create([
            'full_name' => 'Visited Beneficiary',
            'service_group_id' => $group->id,
            'assigned_servant_id' => $servant->id,
        ]);
        $outOfScope = Beneficiary::factory()->create([
            'full_name' => 'Hidden Visit Beneficiary',
            'service_group_id' => $otherGroup->id,
            'assigned_servant_id' => null,
        ]);

        Visit::factory()->create([
            'beneficiary_id' => $inScope->id,
            'created_by' => $servant->id,
            'feedback' => 'Scoped visit feedback',
            'visit_date' => now(),
        ]);
        Visit::factory()->create([
            'beneficiary_id' => $outOfScope->id,
            'feedback' => 'Hidden visit feedback',
            'visit_date' => now(),
        ]);

        $this->actingAs($servant)
            ->get(route('app.visits'))
            ->assertOk()
            ->assertSeeLivewire(VisitsPage::class)
            ->assertSee($inScope->full_name)
            ->assertDontSee($outOfScope->full_name);
    }

    #[Test]
    public function scheduled_visits_page_uses_dedicated_component_and_keeps_role_scope(): void
    {
        $group = ServiceGroup::factory()->create();
        $otherGroup = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $inScope = Beneficiary::factory()->create([
            'full_name' => 'Scheduled Beneficiary',
            'service_group_id' => $group->id,
            'assigned_servant_id' => $servant->id,
        ]);
        $outOfScope = Beneficiary::factory()->create([
            'full_name' => 'Hidden Scheduled Beneficiary',
            'service_group_id' => $otherGroup->id,
            'assigned_servant_id' => null,
        ]);

        ScheduledVisit::factory()->create([
            'beneficiary_id' => $inScope->id,
            'assigned_servant_id' => $servant->id,
            'scheduled_date' => now()->addDay()->toDateString(),
            'status' => 'pending',
        ])->servants()->sync([$servant->id]);
        ScheduledVisit::factory()->create([
            'beneficiary_id' => $outOfScope->id,
            'scheduled_date' => now()->addDay()->toDateString(),
            'status' => 'pending',
        ]);

        $this->actingAs($servant)
            ->get(route('app.scheduled-visits'))
            ->assertOk()
            ->assertSeeLivewire(ScheduledVisitsPage::class)
            ->assertSee($inScope->full_name)
            ->assertDontSee($outOfScope->full_name);
    }

    #[Test]
    public function prayer_requests_page_uses_dedicated_component_and_keeps_role_scope(): void
    {
        $group = ServiceGroup::factory()->create();
        $otherGroup = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $inScope = Beneficiary::factory()->create([
            'full_name' => 'Prayer Beneficiary',
            'service_group_id' => $group->id,
            'assigned_servant_id' => $servant->id,
        ]);
        $outOfScope = Beneficiary::factory()->create([
            'full_name' => 'Hidden Prayer Beneficiary',
            'service_group_id' => $otherGroup->id,
            'assigned_servant_id' => null,
        ]);

        PrayerRequest::factory()->create([
            'beneficiary_id' => $inScope->id,
            'title' => 'Scoped Prayer Request',
            'status' => 'open',
        ]);
        PrayerRequest::factory()->create([
            'beneficiary_id' => $outOfScope->id,
            'title' => 'Hidden Prayer Request',
            'status' => 'open',
        ]);

        $this->actingAs($servant)
            ->get(route('app.prayer-requests'))
            ->assertOk()
            ->assertSeeLivewire(PrayerRequestsPage::class)
            ->assertSee('Scoped Prayer Request')
            ->assertDontSee('Hidden Prayer Request');
    }

    #[Test]
    public function medical_files_page_uses_dedicated_component_and_keeps_role_scope(): void
    {
        $group = ServiceGroup::factory()->create();
        $otherGroup = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $inScope = Beneficiary::factory()->create([
            'full_name' => 'Medical Beneficiary',
            'service_group_id' => $group->id,
            'assigned_servant_id' => $servant->id,
        ]);
        $outOfScope = Beneficiary::factory()->create([
            'full_name' => 'Hidden Medical Beneficiary',
            'service_group_id' => $otherGroup->id,
            'assigned_servant_id' => null,
        ]);

        MedicalFile::factory()->create([
            'beneficiary_id' => $inScope->id,
            'title' => 'Scoped Medical File',
            'file_type' => 'report',
        ]);
        MedicalFile::factory()->create([
            'beneficiary_id' => $outOfScope->id,
            'title' => 'Hidden Medical File',
            'file_type' => 'document',
        ]);

        $this->actingAs($servant)
            ->get(route('app.medical-files'))
            ->assertOk()
            ->assertSeeLivewire(MedicalFilesPage::class)
            ->assertSee('Scoped Medical File')
            ->assertDontSee('Hidden Medical File');
    }

    #[Test]
    public function reports_page_only_shows_links_allowed_for_role(): void
    {
        $group = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $familyLeader = $this->createFamilyLeader($group);

        $this->actingAs($servant)
            ->get(route('app.reports'))
            ->assertOk()
            ->assertSeeLivewire(ReportsPage::class)
            ->assertSee('تقرير المخدومين')
            ->assertDontSee('تقرير الزيارات')
            ->assertDontSee('تقرير غير المزورين');

        $this->actingAs($familyLeader)
            ->get(route('app.reports'))
            ->assertOk()
            ->assertSee('تقرير المخدومين')
            ->assertSee('تقرير الزيارات')
            ->assertSee('تقرير غير المزورين');
    }

    #[Test]
    public function app_resource_routes_use_dedicated_livewire_pages(): void
    {
        $expected = [
            'app.beneficiaries' => BeneficiariesPage::class,
            'app.visits' => VisitsPage::class,
            'app.scheduled-visits' => ScheduledVisitsPage::class,
            'app.prayer-requests' => PrayerRequestsPage::class,
            'app.medical-files' => MedicalFilesPage::class,
            'app.reports' => ReportsPage::class,
            'app.users' => UsersPage::class,
            'app.service-groups' => ServiceGroupsPage::class,
        ];

        foreach ($expected as $routeName => $component) {
            $uses = Route::getRoutes()->getByName($routeName)?->getAction('uses');

            $this->assertSame($component, is_string($uses) ? strtok($uses, '@') : $uses);
        }
    }
}
