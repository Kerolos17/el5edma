<?php

declare(strict_types=1);

namespace Tests\Feature\Servant;

use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class OfflineVisitSyncControllerTest extends TestCase
{
    use RefreshDatabase, CreatesTestUsers;

    private function validPayload(int $beneficiaryId): array
    {
        return [
            'beneficiary_id'    => $beneficiaryId,
            'visitType'         => 'home_visit',
            'beneficiaryStatus' => 'good',
            'durationMinutes'   => 45,
            'feedback'          => 'الزيارة كانت جيدة',
            'isCritical'        => false,
            'needsFamilyLeader' => false,
            'needsServiceLeader'=> false,
            'queuedAt'          => now()->subMinutes(5)->getTimestampMs(),
        ];
    }

    #[Test]
    public function unauthenticated_request_returns_401(): void
    {
        $this->postJson(route('servant.visits.sync'), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function valid_visit_is_saved_and_returns_201(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        $beneficiary = Beneficiary::factory()->create([
            'assigned_servant_id' => $servant->id,
            'service_group_id'    => $group->id,
        ]);

        $response = $this->actingAs($servant)
            ->postJson(route('servant.visits.sync'), $this->validPayload($beneficiary->id));

        $response->assertCreated()
            ->assertJsonStructure(['id']);

        $this->assertDatabaseHas('visits', [
            'beneficiary_id'     => $beneficiary->id,
            'type'               => 'home_visit',
            'beneficiary_status' => 'good',
            'duration_minutes'   => 45,
            'is_critical'        => false,
            'created_by'         => $servant->id,
        ]);
    }

    #[Test]
    public function servant_cannot_sync_visit_for_unowned_beneficiary_and_gets_409(): void
    {
        $group        = ServiceGroup::factory()->create();
        $servant      = $this->createServant($group);
        $otherGroup   = ServiceGroup::factory()->create();

        // Beneficiary belongs to a completely different group with no assignment to our servant
        $beneficiary = Beneficiary::factory()->create([
            'service_group_id'    => $otherGroup->id,
            'assigned_servant_id' => null,
        ]);

        $this->actingAs($servant)
            ->postJson(route('servant.visits.sync'), $this->validPayload($beneficiary->id))
            ->assertStatus(409)
            ->assertJsonStructure(['message']);
    }

    #[Test]
    public function validation_fails_for_missing_required_fields(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        $this->actingAs($servant)
            ->postJson(route('servant.visits.sync'), [])
            ->assertUnprocessable();
    }
}
