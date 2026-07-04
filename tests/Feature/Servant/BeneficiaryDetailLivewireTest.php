<?php

declare(strict_types=1);

namespace Tests\Feature\Servant;

use App\Livewire\Servant\BeneficiaryDetail;
use App\Models\Beneficiary;
use App\Models\PrayerRequest;
use App\Models\ServiceGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class BeneficiaryDetailLivewireTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    #[Test]
    public function servant_can_view_their_beneficiary_detail(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $b       = Beneficiary::factory()->create([
            'assigned_servant_id' => $servant->id,
            'service_group_id'    => $group->id,
        ]);

        Livewire::actingAs($servant)
            ->test(BeneficiaryDetail::class, ['beneficiary' => $b])
            ->assertOk();
    }

    #[Test]
    public function servant_cannot_view_beneficiary_from_different_group(): void
    {
        $group1  = ServiceGroup::factory()->create();
        $group2  = ServiceGroup::factory()->create();
        $servant = $this->createServant($group1);

        // Beneficiary belongs to group2, not assigned to this servant
        $other = Beneficiary::factory()->create([
            'service_group_id'    => $group2->id,
            'assigned_servant_id' => null,
        ]);

        Livewire::actingAs($servant)
            ->test(BeneficiaryDetail::class, ['beneficiary' => $other])
            ->assertForbidden();
    }

    #[Test]
    public function detail_shows_open_prayer_requests(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $b       = Beneficiary::factory()->create([
            'assigned_servant_id' => $servant->id,
            'service_group_id'    => $group->id,
        ]);

        $openPr = PrayerRequest::factory()->create([
            'beneficiary_id' => $b->id,
            'status'         => 'open',
            'created_by'     => $servant->id,
        ]);
        $closedPr = PrayerRequest::factory()->create([
            'beneficiary_id' => $b->id,
            'status'         => 'closed',
            'created_by'     => $servant->id,
        ]);

        Livewire::actingAs($servant)
            ->test(BeneficiaryDetail::class, ['beneficiary' => $b])
            ->assertViewHas('openPrayerRequests', fn ($prs) => $prs->contains('id', $openPr->id))
            ->assertViewHas('openPrayerRequests', fn ($prs) => ! $prs->contains('id', $closedPr->id));
    }

    #[Test]
    public function visit_this_dispatches_open_wizard_for(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $b       = Beneficiary::factory()->create([
            'assigned_servant_id' => $servant->id,
            'service_group_id'    => $group->id,
        ]);

        Livewire::actingAs($servant)
            ->test(BeneficiaryDetail::class, ['beneficiary' => $b])
            ->call('visitThis')
            ->assertDispatched('open-wizard-for', beneficiaryId: $b->id);
    }
}
