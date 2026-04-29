<?php

declare(strict_types=1);

namespace Tests\Feature\Servant;

use App\Livewire\Servant\VisitList;
use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class VisitListLivewireTest extends TestCase
{
    use RefreshDatabase, CreatesTestUsers;

    #[Test]
    public function servant_sees_only_visits_on_their_group_beneficiaries(): void
    {
        $group1  = ServiceGroup::factory()->create();
        $group2  = ServiceGroup::factory()->create();
        $servant = $this->createServant($group1);
        $other   = $this->createServant($group2);

        $bInScope  = Beneficiary::factory()->create(['service_group_id' => $group1->id]);
        $bOutScope = Beneficiary::factory()->create(['service_group_id' => $group2->id]);

        $mine    = Visit::factory()->create(['created_by' => $servant->id, 'beneficiary_id' => $bInScope->id,  'visit_date' => now()]);
        $notMine = Visit::factory()->create(['created_by' => $other->id,   'beneficiary_id' => $bOutScope->id, 'visit_date' => now()]);

        Livewire::actingAs($servant)
            ->test(VisitList::class)
            ->assertViewHas('visits', fn ($v) => $v->contains('id', $mine->id))
            ->assertViewHas('visits', fn ($v) => ! $v->contains('id', $notMine->id));
    }

    #[Test]
    public function filter_month_shows_only_current_month_visits(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $b       = Beneficiary::factory()->create(['service_group_id' => $group->id]);

        $thisMonth = Visit::factory()->create(['created_by' => $servant->id, 'beneficiary_id' => $b->id, 'visit_date' => now()]);
        $lastMonth = Visit::factory()->create(['created_by' => $servant->id, 'beneficiary_id' => $b->id, 'visit_date' => now()->subMonth()]);

        Livewire::actingAs($servant)
            ->test(VisitList::class)
            ->set('filter', 'month')
            ->assertViewHas('visits', fn ($v) => $v->contains('id', $thisMonth->id))
            ->assertViewHas('visits', fn ($v) => ! $v->contains('id', $lastMonth->id));
    }

    #[Test]
    public function filter_critical_shows_only_critical_visits(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $b       = Beneficiary::factory()->create(['service_group_id' => $group->id]);

        // Component filters: ->where('is_critical', true) — no resolved check
        $critical = Visit::factory()->create([
            'created_by'    => $servant->id,
            'beneficiary_id' => $b->id,
            'visit_date'     => now(),
            'is_critical'    => true,
        ]);
        $normal = Visit::factory()->create([
            'created_by'    => $servant->id,
            'beneficiary_id' => $b->id,
            'visit_date'     => now(),
            'is_critical'    => false,
        ]);

        Livewire::actingAs($servant)
            ->test(VisitList::class)
            ->set('filter', 'critical')
            ->assertViewHas('visits', fn ($v) => $v->contains('id', $critical->id))
            ->assertViewHas('visits', fn ($v) => ! $v->contains('id', $normal->id));
    }
}
