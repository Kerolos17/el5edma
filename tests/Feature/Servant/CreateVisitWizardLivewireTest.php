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
}
