<?php

declare(strict_types=1);

namespace Tests\Feature\Servant;

use App\Livewire\Servant\CreateVisitWizard;
use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class CreateVisitWizardLivewireTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    #[Test]
    public function wizard_renders_translated_accessibility_labels(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        Livewire::actingAs($servant)
            ->test(CreateVisitWizard::class)
            ->set('open', true)
            ->assertSee(__('web_app.forms.wizard.steps'))
            ->assertSee(__('web_app.forms.wizard.step_beneficiary'))
            ->assertSee(__('web_app.forms.wizard.navigation'))
            ->assertDontSee('web_app.wizard');
    }

    #[Test]
    public function step1_requires_beneficiary_selection(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        Livewire::actingAs($servant)
            ->test(CreateVisitWizard::class)
            ->set('open', true)
            ->call('nextStep')
            ->assertHasErrors(['selectedBeneficiaryId' => 'required']);
    }

    #[Test]
    public function step2_requires_visit_type(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $b       = Beneficiary::factory()->create([
            'assigned_servant_id' => $servant->id,
            'service_group_id'    => $group->id,
        ]);

        // step is #[Locked], so advance via nextStep() after selecting beneficiary
        Livewire::actingAs($servant)
            ->test(CreateVisitWizard::class)
            ->call('selectBeneficiary', $b->id)
            ->call('nextStep')       // step 1 -> 2 (passes because beneficiary selected)
            ->call('nextStep')       // step 2 -> validates visitType
            ->assertHasErrors(['visitType' => 'required']);
    }

    #[Test]
    public function submit_creates_visit_and_dispatches_events(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $b       = Beneficiary::factory()->create([
            'assigned_servant_id' => $servant->id,
            'service_group_id'    => $group->id,
        ]);

        Livewire::actingAs($servant)
            ->test(CreateVisitWizard::class)
            ->call('selectBeneficiary', $b->id)
            ->set('visitType', 'home_visit')
            ->set('beneficiaryStatus', 'good')
            ->set('durationMinutes', 60)
            ->call('submit')
            ->assertDispatched('visit-saved')
            ->assertDispatched('toast')
            ->assertSet('open', false);

        $this->assertDatabaseHas('visits', [
            'beneficiary_id'     => $b->id,
            'type'               => 'home_visit',
            'beneficiary_status' => 'good',
            'created_by'         => $servant->id,
        ]);
    }

    #[Test]
    public function servant_cannot_submit_visit_for_unowned_beneficiary(): void
    {
        $group1  = ServiceGroup::factory()->create();
        $group2  = ServiceGroup::factory()->create();
        $servant = $this->createServant($group1);

        // Beneficiary belongs to a different group and has no assigned_servant_id
        $other = Beneficiary::factory()->create(['service_group_id' => $group2->id]);

        // selectedBeneficiaryId is a plain public property (not #[Locked]),
        // so it can be set directly — simulating a forged Livewire payload.
        // submit() calls firstOrFail() scoped to ownedBeneficiaryQuery(),
        // which throws ModelNotFoundException (-> 404) for unowned beneficiaries.
        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($servant)
            ->test(CreateVisitWizard::class)
            ->set('selectedBeneficiaryId', $other->id)
            ->set('visitType', 'home_visit')
            ->set('beneficiaryStatus', 'good')
            ->call('submit');
    }

    #[Test]
    public function open_wizard_for_pre_selects_beneficiary_at_step2(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $b       = Beneficiary::factory()->create([
            'assigned_servant_id' => $servant->id,
            'service_group_id'    => $group->id,
        ]);

        Livewire::actingAs($servant)
            ->test(CreateVisitWizard::class)
            ->dispatch('open-wizard-for', beneficiaryId: $b->id)
            ->assertSet('selectedBeneficiaryId', $b->id)
            ->assertSet('step', 2)
            ->assertSet('open', true);
    }

    #[Test]
    public function service_leader_without_managed_groups_cannot_submit_visit(): void
    {
        // Phase 1 authz: submit() must enforce can('create', Visit) BEFORE
        // validation/ownership. A service leader leading zero groups fails
        // VisitPolicy::create and gets 403 (not a validation error).
        $lonelyLeader = $this->createServiceLeader();

        Livewire::actingAs($lonelyLeader)
            ->test(CreateVisitWizard::class)
            ->call('submit')
            ->assertForbidden();
    }

    #[Test]
    public function offline_queue_count_event_updates_pending_badge(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        // Regression: offline-queue.js dispatches {count: n}; the listener
        // must resolve it (was: array $payload -> BindingResolutionException -> 500).
        Livewire::actingAs($servant)
            ->test(CreateVisitWizard::class)
            ->dispatch('offlineQueueCount', count: 2)
            ->assertSet('offlineCount', 2)
            ->assertDispatched('offlineQueueCount');
    }

    #[Test]
    public function offline_queue_count_accepts_browser_positional_payload(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        // Production regression: the browser's Livewire.dispatch() delivers
        // the payload as ONE positional array (not named params), which
        // threw TypeError (masked as 419 with debug off). Must not throw.
        Livewire::actingAs($servant)
            ->test(CreateVisitWizard::class)
            ->dispatch('offlineQueueCount', ['count' => 3])
            ->assertSet('offlineCount', 3)
            ->assertDispatched('offlineQueueCount');
    }

    #[Test]
    public function offline_sync_conflict_event_bridges_to_browser(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        Livewire::actingAs($servant)
            ->test(CreateVisitWizard::class)
            ->dispatch('offlineSyncConflict')
            ->assertDispatched('offlineSyncConflict');
    }
}
