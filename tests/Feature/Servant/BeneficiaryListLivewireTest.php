<?php

declare(strict_types=1);

namespace Tests\Feature\Servant;

use App\Livewire\Servant\BeneficiaryList;
use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class BeneficiaryListLivewireTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    #[Test]
    public function servant_only_sees_beneficiaries_from_their_group(): void
    {
        $group1  = ServiceGroup::factory()->create();
        $group2  = ServiceGroup::factory()->create();
        $servant = $this->createServant($group1);

        $inScope  = Beneficiary::factory()->create(['service_group_id' => $group1->id]);
        $outScope = Beneficiary::factory()->create(['service_group_id' => $group2->id]);

        Livewire::actingAs($servant)
            ->test(BeneficiaryList::class)
            ->assertViewHas('beneficiaries', fn ($b) => $b->contains('id', $inScope->id))
            ->assertViewHas('beneficiaries', fn ($b) => ! $b->contains('id', $outScope->id));
    }

    #[Test]
    public function search_filters_by_name(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        Beneficiary::factory()->create(['full_name' => 'مريم سمير',    'service_group_id' => $group->id]);
        Beneficiary::factory()->create(['full_name' => 'يوسف إبراهيم', 'service_group_id' => $group->id]);

        Livewire::actingAs($servant)
            ->test(BeneficiaryList::class)
            ->set('search', 'مريم')
            ->assertViewHas('beneficiaries', fn ($b) => $b->total() === 1 && $b->items()[0]->full_name === 'مريم سمير');
    }

    #[Test]
    public function filter_mine_shows_only_assigned_beneficiaries(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $other   = $this->createServant($group);

        $mine    = Beneficiary::factory()->create(['assigned_servant_id' => $servant->id, 'service_group_id' => $group->id]);
        $notMine = Beneficiary::factory()->create(['assigned_servant_id' => $other->id,   'service_group_id' => $group->id]);

        Livewire::actingAs($servant)
            ->test(BeneficiaryList::class)
            ->set('filter', 'mine')
            ->assertViewHas('beneficiaries', fn ($b) => $b->contains('id', $mine->id))
            ->assertViewHas('beneficiaries', fn ($b) => ! $b->contains('id', $notMine->id));
    }
}
