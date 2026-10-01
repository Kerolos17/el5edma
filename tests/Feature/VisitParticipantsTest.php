<?php

namespace Tests\Feature;

use App\Livewire\Servant\CreateVisitWizard;
use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VisitParticipantsTest extends TestCase
{
    use RefreshDatabase;

    public function test_wizard_accepts_fellow_servants_of_the_same_group(): void
    {
        $group   = ServiceGroup::factory()->create();
        $creator = User::factory()->create(['service_group_id' => $group->id]);
        $fellow  = User::factory()->create([
            'role'             => 'servant',
            'is_active'        => true,
            'service_group_id' => $group->id,
        ]);
        $beneficiary = Beneficiary::factory()->create(['service_group_id' => $group->id]);

        Livewire::actingAs($creator)
            ->test(CreateVisitWizard::class)
            ->set('selectedBeneficiaryId', $beneficiary->id)
            ->set('visitType', 'home_visit')
            ->set('beneficiaryStatus', 'good')
            ->set('participantServants', [$fellow->id])
            ->call('submit')
            ->assertHasNoErrors();

        $visit = Visit::latest('id')->first();

        $this->assertSame(
            [$creator->id, $fellow->id],
            $visit->servants()->pluck('users.id')->sort()->values()->all(),
        );
    }

    public function test_wizard_filters_out_participants_outside_the_beneficiary_group(): void
    {
        $group      = ServiceGroup::factory()->create();
        $otherGroup = ServiceGroup::factory()->create();
        $creator    = User::factory()->create(['service_group_id' => $group->id]);
        $outsider   = User::factory()->create([
            'role'             => 'servant',
            'is_active'        => true,
            'service_group_id' => $otherGroup->id,
        ]);
        $beneficiary = Beneficiary::factory()->create(['service_group_id' => $group->id]);

        Livewire::actingAs($creator)
            ->test(CreateVisitWizard::class)
            ->set('selectedBeneficiaryId', $beneficiary->id)
            ->set('visitType', 'phone_call')
            ->set('beneficiaryStatus', 'good')
            ->set('participantServants', [$outsider->id])
            ->call('submit')
            ->assertHasNoErrors(); // silently ignored — never an error leak

        $visit = Visit::latest('id')->first();

        $this->assertSame(
            [$creator->id],
            $visit->servants()->pluck('users.id')->all(),
        );
    }

    public function test_offline_sync_accepts_participants(): void
    {
        $group   = ServiceGroup::factory()->create();
        $creator = User::factory()->create(['service_group_id' => $group->id]);
        $fellow  = User::factory()->create([
            'role'             => 'servant',
            'is_active'        => true,
            'service_group_id' => $group->id,
        ]);
        Beneficiary::factory()->create(['service_group_id' => $group->id]);

        $response = $this->actingAs($creator)
            ->postJson('/servant/visits/sync', [
                'beneficiary_id'    => 1,
                'visitType'         => 'home_visit',
                'beneficiaryStatus' => 'good',
                'clientUuid'        => 'offline-participants-1',
                'servants'          => [$fellow->id],
            ]);

        $response->assertCreated();

        $visit = Visit::latest('id')->first();

        $this->assertSame(
            [$creator->id, $fellow->id],
            $visit->servants()->pluck('users.id')->sort()->values()->all(),
        );
    }

    public function test_offline_sync_ignores_participants_outside_the_group(): void
    {
        $group      = ServiceGroup::factory()->create();
        $otherGroup = ServiceGroup::factory()->create();
        $creator    = User::factory()->create(['service_group_id' => $group->id]);
        User::factory()->create([
            'role'             => 'servant',
            'is_active'        => true,
            'service_group_id' => $otherGroup->id,
        ]);
        Beneficiary::factory()->create(['service_group_id' => $group->id]);

        $this->actingAs($creator)
            ->postJson('/servant/visits/sync', [
                'beneficiary_id'    => 1,
                'visitType'         => 'home_visit',
                'beneficiaryStatus' => 'good',
                'clientUuid'        => 'offline-participants-2',
                'servants'          => [999],
            ])
            ->assertCreated();

        $visit = Visit::latest('id')->first();

        $this->assertSame([$creator->id], $visit->servants()->pluck('users.id')->all());
    }
}
