<?php

declare(strict_types=1);

namespace Tests\Feature\Servant;

use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class OfflineVisitSyncControllerTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    private function validPayload(int $beneficiaryId): array
    {
        return [
            'beneficiary_id'     => $beneficiaryId,
            'visitType'          => 'home_visit',
            'beneficiaryStatus'  => 'good',
            'durationMinutes'    => 45,
            'feedback'           => 'الزيارة كانت جيدة',
            'isCritical'         => false,
            'needsFamilyLeader'  => false,
            'needsServiceLeader' => false,
            'queuedAt'           => now()->subMinutes(5)->getTimestampMs(),
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
        $group      = ServiceGroup::factory()->create();
        $servant    = $this->createServant($group);
        $otherGroup = ServiceGroup::factory()->create();

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
    public function servant_can_sync_visit_for_beneficiary_owned_via_group(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        // Beneficiary belongs to servant's group but is not directly assigned
        $beneficiary = Beneficiary::factory()->create([
            'service_group_id'    => $group->id,
            'assigned_servant_id' => null,
        ]);

        $this->actingAs($servant)
            ->postJson(route('servant.visits.sync'), $this->validPayload($beneficiary->id))
            ->assertCreated();
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

    #[Test]
    public function service_leader_without_managed_groups_gets_403_on_sync(): void
    {
        $group        = ServiceGroup::factory()->create();
        $lonelyLeader = $this->createServiceLeader();
        $beneficiary  = Beneficiary::factory()->create([
            'service_group_id' => $group->id,
        ]);

        $this->actingAs($lonelyLeader)
            ->postJson(route('servant.visits.sync'), $this->validPayload($beneficiary->id))
            ->assertForbidden();
    }

    #[Test]
    public function replay_with_same_client_uuid_returns_original_without_duplicate(): void
    {
        $group       = ServiceGroup::factory()->create();
        $servant     = $this->createServant($group);
        $beneficiary = Beneficiary::factory()->create([
            'assigned_servant_id' => $servant->id,
            'service_group_id'    => $group->id,
        ]);

        $payload               = $this->validPayload($beneficiary->id);
        $payload['clientUuid'] = 'test-uuid-replay-001';

        $first = $this->actingAs($servant)
            ->postJson(route('servant.visits.sync'), $payload);
        $first->assertCreated()->assertJsonStructure(['id']);

        $second = $this->actingAs($servant)
            ->postJson(route('servant.visits.sync'), $payload);
        $second->assertOk()->assertJson(['id' => $first->json('id'), 'duplicate' => true]);

        $this->assertSame(1, Visit::where('client_uuid', 'test-uuid-replay-001')->count());
    }

    #[Test]
    public function seconds_epoch_queued_at_falls_back_to_now(): void
    {
        $group       = ServiceGroup::factory()->create();
        $servant     = $this->createServant($group);
        $beneficiary = Beneficiary::factory()->create([
            'assigned_servant_id' => $servant->id,
            'service_group_id'    => $group->id,
        ]);

        $payload             = $this->validPayload($beneficiary->id);
        $payload['queuedAt'] = 1700000000; // seconds, not ms (would be 1970 if misread)

        $response = $this->actingAs($servant)
            ->postJson(route('servant.visits.sync'), $payload);
        $response->assertCreated();

        $visit = Visit::findOrFail($response->json('id'));

        // Must be ~now, not 1970 and not a far-future misread.
        $this->assertTrue($visit->visit_date->greaterThan(now()->subMinutes(5)));
        $this->assertTrue($visit->visit_date->lessThanOrEqualTo(now()->addMinute()));
    }
}
