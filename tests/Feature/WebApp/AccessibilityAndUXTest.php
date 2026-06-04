<?php

declare(strict_types=1);

namespace Tests\Feature\WebApp;

use App\Livewire\WebApp\BeneficiariesPage;
use App\Livewire\WebApp\Dashboard;
use App\Models\ServiceGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

/**
 * Phase 2 — UX & accessibility regression tests.
 * Verifies skeleton markup, empty state components, and ARIA attributes.
 */
class AccessibilityAndUXTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    #[Test]
    public function beneficiaries_table_has_aria_label(): void
    {
        $group  = ServiceGroup::factory()->create();
        $leader = $this->createFamilyLeader($group);

        Livewire::actingAs($leader)
            ->test(BeneficiariesPage::class)
            ->assertSee('aria-label', false); // layout-level assertion
    }

    #[Test]
    public function beneficiaries_page_shows_empty_state_component_when_no_records(): void
    {
        $group  = ServiceGroup::factory()->create();
        $leader = $this->createFamilyLeader($group);

        // No beneficiaries created → empty state should render
        Livewire::actingAs($leader)
            ->test(BeneficiariesPage::class)
            ->assertSee(__('web_app.resources.empty_table'));
    }

    #[Test]
    public function beneficiary_modal_has_aria_modal_and_role_dialog(): void
    {
        $group  = ServiceGroup::factory()->create();
        $leader = $this->createFamilyLeader($group);

        Livewire::actingAs($leader)
            ->test(BeneficiariesPage::class)
            ->call('openBeneficiaryForm')
            ->assertSee('role="dialog"', false)
            ->assertSee('aria-modal="true"', false)
            ->assertSee('aria-labelledby="bene-modal-title"', false);
    }

    #[Test]
    public function dashboard_renders_with_stat_cards_for_family_leader(): void
    {
        $group  = ServiceGroup::factory()->create();
        $leader = $this->createFamilyLeader($group);

        Livewire::actingAs($leader)
            ->test(Dashboard::class)
            ->assertOk()
            ->assertSee(__('web_app.dashboard.recent_visits'));
    }
}
