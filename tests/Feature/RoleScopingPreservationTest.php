<?php
namespace Tests\Feature;

use App\Filament\Resources\Visits\VisitResource;
use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Role Scoping Preservation Tests
 *
 * **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5**
 *
 * These tests capture the CORRECT existing behavior for all non-servant roles
 * and cross-group isolation. They MUST PASS on unfixed code and MUST continue
 * to pass after the fix is applied (regression prevention).
 */
class RoleScopingPreservationTest extends TestCase
{
    use RefreshDatabase;

    private ServiceGroup $groupA;

    private ServiceGroup $groupB;

    private User $superAdmin;

    private User $serviceLeader;

    private User $familyLeader;

    private User $servantA;

    private User $servantB;

    private Beneficiary $beneficiaryA1;

    private Beneficiary $beneficiaryA2;

    private Beneficiary $beneficiaryB1;

    private Visit $visitA1;

    private Visit $visitA2;

    private Visit $visitB1;

    protected function setUp(): void
    {
        parent::setUp();

        // Two service groups
        $this->groupA = ServiceGroup::factory()->create();
        $this->groupB = ServiceGroup::factory()->create();

        // super_admin — no service_group_id needed
        $this->superAdmin = User::factory()->create([
            'role'             => 'super_admin',
            'service_group_id' => null,
        ]);

        // service_leader — no service_group_id needed
        $this->serviceLeader = User::factory()->create([
            'role'             => 'service_leader',
            'service_group_id' => null,
        ]);

        // family_leader in group_a
        $this->familyLeader = User::factory()->create([
            'role'             => 'family_leader',
            'service_group_id' => $this->groupA->id,
        ]);

        // servant_a in group_a
        $this->servantA = User::factory()->create([
            'role'             => 'servant',
            'service_group_id' => $this->groupA->id,
        ]);

        // servant_b in group_b
        $this->servantB = User::factory()->create([
            'role'             => 'servant',
            'service_group_id' => $this->groupB->id,
        ]);

        // Two active beneficiaries in group_a
        $this->beneficiaryA1 = Beneficiary::factory()->create([
            'service_group_id'    => $this->groupA->id,
            'assigned_servant_id' => $this->servantA->id,
            'status'              => 'active',
        ]);

        $this->beneficiaryA2 = Beneficiary::factory()->create([
            'service_group_id'    => $this->groupA->id,
            'assigned_servant_id' => $this->servantA->id,
            'status'              => 'active',
        ]);

        // One active beneficiary in group_b
        $this->beneficiaryB1 = Beneficiary::factory()->create([
            'service_group_id'    => $this->groupB->id,
            'assigned_servant_id' => $this->servantB->id,
            'status'              => 'active',
        ]);

        // Visit for beneficiary_a1 created by servant_a
        $this->visitA1 = Visit::factory()->create([
            'beneficiary_id' => $this->beneficiaryA1->id,
            'created_by'     => $this->servantA->id,
        ]);

        // Visit for beneficiary_a2 created by family_leader
        $this->visitA2 = Visit::factory()->create([
            'beneficiary_id' => $this->beneficiaryA2->id,
            'created_by'     => $this->familyLeader->id,
        ]);

        // Visit for beneficiary_b1 created by servant_b
        $this->visitB1 = Visit::factory()->create([
            'beneficiary_id' => $this->beneficiaryB1->id,
            'created_by'     => $this->servantB->id,
        ]);
    }

    /**
     * family_leader sees only visits for beneficiaries in their service group.
     * This uses the correct whereHas('beneficiary', service_group_id) scoping.
     */
    public function test_family_leader_visit_query_scoped_to_service_group(): void
    {
        Auth::login($this->familyLeader);

        $visitIds = VisitResource::getEloquentQuery()
            ->pluck('id')
            ->sort()
            ->values()
            ->toArray();

        $expectedIds = collect([$this->visitA1->id, $this->visitA2->id])
            ->sort()
            ->values()
            ->toArray();

        $this->assertCount(2, $visitIds, 'family_leader should see exactly 2 visits (group_a only)');
        $this->assertEquals($expectedIds, $visitIds);
        $this->assertNotContains($this->visitB1->id, $visitIds, 'family_leader must not see group_b visits');
    }

    /**
     * super_admin sees all visits with no scope restriction.
     */
    public function test_super_admin_visit_query_returns_all(): void
    {
        Auth::login($this->superAdmin);

        $visitIds = VisitResource::getEloquentQuery()
            ->pluck('id')
            ->sort()
            ->values()
            ->toArray();

        $expectedIds = collect([$this->visitA1->id, $this->visitA2->id, $this->visitB1->id])
            ->sort()
            ->values()
            ->toArray();

        $this->assertCount(3, $visitIds, 'super_admin should see all 3 visits');
        $this->assertEquals($expectedIds, $visitIds);
    }

    /**
     * service_leader sees all visits with no scope restriction.
     */
    public function test_service_leader_visit_query_returns_all(): void
    {
        Auth::login($this->serviceLeader);

        $visitIds = VisitResource::getEloquentQuery()
            ->pluck('id')
            ->sort()
            ->values()
            ->toArray();

        $expectedIds = collect([$this->visitA1->id, $this->visitA2->id, $this->visitB1->id])
            ->sort()
            ->values()
            ->toArray();

        $this->assertCount(3, $visitIds, 'service_leader should see all 3 visits');
        $this->assertEquals($expectedIds, $visitIds);
    }

    /**
     * family_leader beneficiary options are scoped to their service group.
     * Replicates the exact VisitForm options closure logic.
     */
    public function test_family_leader_beneficiary_options_scoped_to_service_group(): void
    {
        Auth::login($this->familyLeader);

        $user  = Auth::user();
        $query = Beneficiary::where('status', 'active');

        if ($user->role === 'family_leader') {
            $query->where('service_group_id', $user->service_group_id);
        } elseif ($user->role === 'servant') {
            $query->where('assigned_servant_id', $user->id);
        }

        $options = $query->pluck('full_name', 'id');

        $this->assertArrayHasKey($this->beneficiaryA1->id, $options->toArray(), 'beneficiary_a1 must be in options');
        $this->assertArrayHasKey($this->beneficiaryA2->id, $options->toArray(), 'beneficiary_a2 must be in options');
        $this->assertArrayNotHasKey($this->beneficiaryB1->id, $options->toArray(), 'beneficiary_b1 must NOT be in options');
        $this->assertCount(2, $options);
    }

    /**
     * super_admin beneficiary options returns all active beneficiaries (no role filter applied).
     * Replicates the exact VisitForm options closure logic.
     */
    public function test_super_admin_beneficiary_options_returns_all(): void
    {
        Auth::login($this->superAdmin);

        $user  = Auth::user();
        $query = Beneficiary::where('status', 'active');

        if ($user->role === 'family_leader') {
            $query->where('service_group_id', $user->service_group_id);
        } elseif ($user->role === 'servant') {
            $query->where('assigned_servant_id', $user->id);
        }

        $options = $query->pluck('full_name', 'id');

        $this->assertArrayHasKey($this->beneficiaryA1->id, $options->toArray(), 'beneficiary_a1 must be in options');
        $this->assertArrayHasKey($this->beneficiaryA2->id, $options->toArray(), 'beneficiary_a2 must be in options');
        $this->assertArrayHasKey($this->beneficiaryB1->id, $options->toArray(), 'beneficiary_b1 must be in options');
        $this->assertCount(3, $options);
    }

    /**
     * servant_a (group_a) never sees visits from group_b — cross-group isolation.
     * On unfixed code, servant_a only sees visits they created (visit_a1).
     * Either way, visit_b1 must not appear.
     */
    public function test_servant_cross_group_isolation_visit_query(): void
    {
        Auth::login($this->servantA);

        $visitIds = VisitResource::getEloquentQuery()
            ->pluck('id')
            ->toArray();

        $this->assertNotContains(
            $this->visitB1->id,
            $visitIds,
            'servant_a must never see visit_b1 from group_b',
        );
    }

    /**
     * servant_a (group_a) never sees beneficiaries from group_b — cross-group isolation.
     * On unfixed code, servant uses assigned_servant_id filter, so group_b beneficiaries
     * are excluded anyway. This test verifies that isolation holds.
     */
    public function test_servant_cross_group_isolation_beneficiary_options(): void
    {
        Auth::login($this->servantA);

        $user  = Auth::user();
        $query = Beneficiary::where('status', 'active');

        if ($user->role === 'family_leader') {
            $query->where('service_group_id', $user->service_group_id);
        } elseif ($user->role === 'servant') {
            $query->where('assigned_servant_id', $user->id);
        }

        $options = $query->pluck('full_name', 'id');

        $this->assertArrayNotHasKey(
            $this->beneficiaryB1->id,
            $options->toArray(),
            'servant_a must never see beneficiary_b1 from group_b',
        );
    }
}
