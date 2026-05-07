<?php

declare(strict_types=1);

namespace Tests\Feature\Servant;

use App\Livewire\Servant\ScheduledVisitList;
use App\Models\Beneficiary;
use App\Models\ScheduledVisit;
use App\Models\ServiceGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class ScheduledVisitListLivewireTest extends TestCase
{
    use RefreshDatabase, CreatesTestUsers;

    #[Test]
    public function servant_can_see_their_scheduled_visits(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $b       = Beneficiary::factory()->create(['service_group_id' => $group->id]);

        $mine  = ScheduledVisit::factory()->create([
            'assigned_servant_id' => $servant->id,
            'beneficiary_id'      => $b->id,
            'status'              => 'pending',
            'scheduled_date'      => now()->addDay(),
        ]);
        $mine->syncAssignedServants([$servant->id, $this->createServant($group)->id]);
        $other = ScheduledVisit::factory()->create([
            'assigned_servant_id' => $this->createServant($group)->id,
            'beneficiary_id'      => $b->id,
            'status'              => 'pending',
            'scheduled_date'      => now()->addDay(),
        ]);

        Livewire::actingAs($servant)
            ->test(ScheduledVisitList::class)
            ->assertViewHas('scheduledVisits', fn ($sv) => $sv->contains('id', $mine->id))
            ->assertViewHas('scheduledVisits', fn ($sv) => ! $sv->contains('id', $other->id));
    }

    #[Test]
    public function servant_can_cancel_their_scheduled_visit(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $b       = Beneficiary::factory()->create(['assigned_servant_id' => $servant->id]);

        $sv = ScheduledVisit::factory()->create([
            'assigned_servant_id' => $servant->id,
            'beneficiary_id'      => $b->id,
            'status'              => 'pending',
            'scheduled_date'      => now()->addDay(),
        ]);
        $sv->syncAssignedServants([$servant->id, $this->createServant($group)->id]);

        Livewire::actingAs($servant)
            ->test(ScheduledVisitList::class)
            ->call('cancel', $sv->id)
            ->assertDispatched('toast');

        $this->assertDatabaseHas('scheduled_visits', ['id' => $sv->id, 'status' => 'cancelled']);
    }

    #[Test]
    public function servant_cannot_cancel_another_servants_scheduled_visit(): void
    {
        $group    = ServiceGroup::factory()->create();
        $servant1 = $this->createServant($group);
        $servant2 = $this->createServant($group);
        $b        = Beneficiary::factory()->create(['service_group_id' => $group->id]);

        $sv = ScheduledVisit::factory()->create([
            'assigned_servant_id' => $servant2->id,
            'beneficiary_id'      => $b->id,
            'status'              => 'pending',
        ]);

        Livewire::actingAs($servant1)
            ->test(ScheduledVisitList::class)
            ->call('cancel', $sv->id)
            ->assertNotFound();
    }

    #[Test]
    public function filter_upcoming_shows_only_future_pending_visits(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $b       = Beneficiary::factory()->create(['assigned_servant_id' => $servant->id]);

        $upcoming = ScheduledVisit::factory()->create([
            'assigned_servant_id' => $servant->id,
            'beneficiary_id'      => $b->id,
            'status'              => 'pending',
            'scheduled_date'      => now()->addDays(3),
        ]);
        $upcoming->syncAssignedServants([$servant->id, $this->createServant($group)->id]);
        $past = ScheduledVisit::factory()->create([
            'assigned_servant_id' => $servant->id,
            'beneficiary_id'      => $b->id,
            'status'              => 'pending',
            'scheduled_date'      => now()->subDay(),
        ]);

        Livewire::actingAs($servant)
            ->test(ScheduledVisitList::class)
            ->set('filter', 'upcoming')
            ->assertViewHas('scheduledVisits', fn ($sv) => $sv->contains('id', $upcoming->id))
            ->assertViewHas('scheduledVisits', fn ($sv) => ! $sv->contains('id', $past->id));
    }
}
