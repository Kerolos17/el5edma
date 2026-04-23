<?php

namespace Tests\Feature;

use App\Filament\Resources\ScheduledVisits\ScheduledVisitResource;
use App\Models\Beneficiary;
use App\Models\ScheduledVisit;
use App\Models\ServiceGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class ScheduledVisitResourceScopeTest extends TestCase
{
    use CreatesTestUsers;
    use RefreshDatabase;

    public function test_service_leader_query_is_scoped_to_managed_groups(): void
    {
        $groupA        = ServiceGroup::factory()->create();
        $groupB        = ServiceGroup::factory()->create();
        $serviceLeader = $this->createServiceLeader();
        $groupA->update(['service_leader_id' => $serviceLeader->id]);

        $beneficiaryA    = Beneficiary::factory()->create(['service_group_id' => $groupA->id]);
        $beneficiaryB    = Beneficiary::factory()->create(['service_group_id' => $groupB->id]);
        $scheduledVisitA = ScheduledVisit::factory()->create(['beneficiary_id' => $beneficiaryA->id]);
        $scheduledVisitB = ScheduledVisit::factory()->create(['beneficiary_id' => $beneficiaryB->id]);

        $this->actingAs($serviceLeader);

        $ids = ScheduledVisitResource::getEloquentQuery()->pluck('id');

        $this->assertContains($scheduledVisitA->id, $ids);
        $this->assertNotContains($scheduledVisitB->id, $ids);
    }
}
