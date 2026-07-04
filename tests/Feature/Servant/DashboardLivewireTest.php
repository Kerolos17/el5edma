<?php

declare(strict_types=1);

namespace Tests\Feature\Servant;

use App\Livewire\Servant\Dashboard;
use App\Models\Beneficiary;
use App\Models\ScheduledVisit;
use App\Models\ServiceGroup;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class DashboardLivewireTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    #[Test]
    public function dashboard_shows_correct_beneficiary_count(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        Beneficiary::factory()->count(3)->create(['assigned_servant_id' => $servant->id]);
        Beneficiary::factory()->create(); // out of scope

        Livewire::actingAs($servant)
            ->test(Dashboard::class)
            ->assertViewHas('myBeneficiariesCount', 3);
    }

    #[Test]
    public function dashboard_shows_visits_this_month(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $b       = Beneficiary::factory()->create(['assigned_servant_id' => $servant->id]);

        Visit::factory()->count(2)->create([
            'created_by'     => $servant->id,
            'beneficiary_id' => $b->id,
            'visit_date'     => now(),
        ]);
        Visit::factory()->create([
            'created_by'     => $servant->id,
            'beneficiary_id' => $b->id,
            'visit_date'     => now()->subMonth(),
        ]);

        Livewire::actingAs($servant)
            ->test(Dashboard::class)
            ->assertViewHas('visitsThisMonth', 2);
    }

    #[Test]
    public function dashboard_counts_upcoming_scheduled_visits(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $b       = Beneficiary::factory()->create(['assigned_servant_id' => $servant->id]);

        ScheduledVisit::factory()->create([
            'assigned_servant_id' => $servant->id,
            'beneficiary_id'      => $b->id,
            'status'              => 'pending',
            'scheduled_date'      => now()->addDay(),
        ])->syncAssignedServants([$servant->id, $this->createServant($group)->id]);
        ScheduledVisit::factory()->create([
            'assigned_servant_id' => $servant->id,
            'beneficiary_id'      => $b->id,
            'status'              => 'completed',
            'scheduled_date'      => now()->addDay(),
        ]);

        Livewire::actingAs($servant)
            ->test(Dashboard::class)
            ->assertViewHas('scheduledCount', 1);
    }

    #[Test]
    public function visit_saved_event_triggers_refresh(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        $component    = Livewire::actingAs($servant)->test(Dashboard::class);
        $initialCount = $component->viewData('visitsThisMonth');

        $b = Beneficiary::factory()->create(['assigned_servant_id' => $servant->id]);
        Visit::factory()->create(['created_by' => $servant->id, 'beneficiary_id' => $b->id, 'visit_date' => now()]);

        $component->dispatch('visit-saved')
            ->assertViewHas('visitsThisMonth', $initialCount + 1);
    }
}
