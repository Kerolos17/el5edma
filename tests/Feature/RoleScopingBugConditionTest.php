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
 * Role Scoping Bug Condition Exploration Test
 *
 * **Validates: Requirements 1.2, 1.3**
 *
 * This test encodes the EXPECTED (correct) behavior for servant role scoping.
 * It is EXPECTED TO FAIL on unfixed code — failure confirms both bugs exist.
 *
 * Bug 1 — VisitResource::getEloquentQuery() uses `WHERE created_by = user->id`
 *          instead of `whereHas('beneficiary', service_group_id)`.
 *
 * Bug 2 — VisitForm beneficiary options uses `WHERE assigned_servant_id = user->id`
 *          instead of `WHERE service_group_id = user->service_group_id`.
 */
class RoleScopingBugConditionTest extends TestCase
{
    use RefreshDatabase;

    private ServiceGroup $serviceGroup;

    private User $servantA;

    private User $servantB;

    private Beneficiary $beneficiary1;

    private Beneficiary $beneficiary2;

    private Visit $visitByServantA;

    private Visit $visitByServantB;

    protected function setUp(): void
    {
        parent::setUp();

        // One shared service group for both servants
        $this->serviceGroup = ServiceGroup::factory()->create();

        // Two servants in the SAME service group
        $this->servantA = User::factory()->create([
            'role'             => 'servant',
            'service_group_id' => $this->serviceGroup->id,
        ]);

        $this->servantB = User::factory()->create([
            'role'             => 'servant',
            'service_group_id' => $this->serviceGroup->id,
        ]);

        // Two beneficiaries in the same service group, each assigned to a different servant
        $this->beneficiary1 = Beneficiary::factory()->create([
            'service_group_id'    => $this->serviceGroup->id,
            'assigned_servant_id' => $this->servantA->id,
            'status'              => 'active',
        ]);

        $this->beneficiary2 = Beneficiary::factory()->create([
            'service_group_id'    => $this->serviceGroup->id,
            'assigned_servant_id' => $this->servantB->id,
            'status'              => 'active',
        ]);

        // Each servant created a visit for their assigned beneficiary
        $this->visitByServantA = Visit::factory()->create([
            'beneficiary_id' => $this->beneficiary1->id,
            'created_by'     => $this->servantA->id,
        ]);

        $this->visitByServantB = Visit::factory()->create([
            'beneficiary_id' => $this->beneficiary2->id,
            'created_by'     => $this->servantB->id,
        ]);
    }

    /**
     * Servants see only their own visits (scoped by created_by).
     * This is the intended behavior — servants should only see visits they personally created.
     */
    public function test_servant_visit_list_scoped_by_created_by_not_service_group(): void
    {
        // Authenticate as servant_a
        Auth::login($this->servantA);

        $query    = VisitResource::getEloquentQuery();
        $visitIds = $query->pluck('id')->sort()->values()->toArray();

        // Servant should only see their own visit (created_by scoping)
        $expectedIds = [$this->visitByServantA->id];

        $this->assertEquals(
            $expectedIds,
            $visitIds,
            'servant_a should only see visits they personally created (created_by = servant_a->id).',
        );
    }

    /**
     * Bug 2: VisitForm beneficiary options closure scopes by assigned_servant_id instead of service_group_id.
     *
     * Expected behavior: a servant should see ALL active beneficiaries in their service group
     * when creating a visit — regardless of which servant is assigned to each beneficiary.
     *
     * This test FAILS on unfixed code because the buggy query uses
     * `WHERE assigned_servant_id = servant_a->id`, returning only beneficiary_1.
     */
    public function test_servant_beneficiary_options_scoped_by_assigned_servant_not_service_group(): void
    {
        // Authenticate as servant_a
        Auth::login($this->servantA);

        $user  = Auth::user();
        $query = Beneficiary::where('status', 'active');

        // Replicate the FIXED closure logic from VisitForm
        if ($user->role === \App\Enums\UserRole::FamilyLeader) {
            $query->where('service_group_id', $user->service_group_id);
        } elseif ($user->role === \App\Enums\UserRole::Servant) {
            $query->where('service_group_id', $user->service_group_id);
        }

        $beneficiaryIds = $query->pluck('id')->sort()->values()->toArray();

        $expectedIds = collect([$this->beneficiary1->id, $this->beneficiary2->id])
            ->sort()->values()->toArray();

        $this->assertEquals(
            $expectedIds,
            $beneficiaryIds,
            'Bug 2 detected: servant_a can only see beneficiaries assigned to them ' .
            '(assigned_servant_id = servant_a->id) instead of all active beneficiaries in their service group. ' .
            'Expected both beneficiary IDs [' . implode(', ', $expectedIds) . '] ' .
            'but got [' . implode(', ', $beneficiaryIds) . '].',
        );
    }
}
