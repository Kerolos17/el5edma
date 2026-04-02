<?php

namespace Tests\Feature;

use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    // ── BeneficiaryPolicy ──

    #[Test]
    public function super_admin_can_do_everything_on_beneficiaries(): void
    {
        $admin       = User::factory()->create(['role' => 'super_admin']);
        $beneficiary = Beneficiary::factory()->create();

        $this->assertTrue($admin->can('viewAny', Beneficiary::class));
        $this->assertTrue($admin->can('view', $beneficiary));
        $this->assertTrue($admin->can('create', Beneficiary::class));
        $this->assertTrue($admin->can('update', $beneficiary));
        $this->assertTrue($admin->can('delete', $beneficiary));
    }

    #[Test]
    public function servant_can_only_view_beneficiaries_in_their_group(): void
    {
        $group1 = ServiceGroup::factory()->create();
        $group2 = ServiceGroup::factory()->create();

        $servant = User::factory()->create([
            'role'             => 'servant',
            'service_group_id' => $group1->id,
        ]);

        $ownBeneficiary   = Beneficiary::factory()->create(['service_group_id' => $group1->id]);
        $otherBeneficiary = Beneficiary::factory()->create(['service_group_id' => $group2->id]);

        $this->assertTrue($servant->can('view', $ownBeneficiary));
        $this->assertFalse($servant->can('view', $otherBeneficiary));
        $this->assertFalse($servant->can('create', Beneficiary::class));
        $this->assertFalse($servant->can('update', $ownBeneficiary));
        $this->assertFalse($servant->can('delete', $ownBeneficiary));
    }

    #[Test]
    public function family_leader_can_manage_beneficiaries_in_their_group(): void
    {
        $group1 = ServiceGroup::factory()->create();
        $group2 = ServiceGroup::factory()->create();

        $leader = User::factory()->create([
            'role'             => 'family_leader',
            'service_group_id' => $group1->id,
        ]);

        $ownBeneficiary   = Beneficiary::factory()->create(['service_group_id' => $group1->id]);
        $otherBeneficiary = Beneficiary::factory()->create(['service_group_id' => $group2->id]);

        $this->assertTrue($leader->can('create', Beneficiary::class));
        $this->assertTrue($leader->can('view', $ownBeneficiary));
        $this->assertTrue($leader->can('update', $ownBeneficiary));
        $this->assertTrue($leader->can('delete', $ownBeneficiary));

        $this->assertFalse($leader->can('view', $otherBeneficiary));
        $this->assertFalse($leader->can('update', $otherBeneficiary));
        $this->assertFalse($leader->can('delete', $otherBeneficiary));
    }

    // ── VisitPolicy ──

    #[Test]
    public function servant_can_create_visits_but_not_edit_or_delete(): void
    {
        $group = ServiceGroup::factory()->create();

        $servant = User::factory()->create([
            'role'             => 'servant',
            'service_group_id' => $group->id,
        ]);

        $beneficiary = Beneficiary::factory()->create(['service_group_id' => $group->id]);
        $visit       = Visit::factory()->create([
            'beneficiary_id' => $beneficiary->id,
            'created_by'     => $servant->id,
        ]);

        $this->assertTrue($servant->can('create', Visit::class));
        $this->assertTrue($servant->can('view', $visit));
        $this->assertFalse($servant->can('update', $visit));
        $this->assertFalse($servant->can('delete', $visit));
    }

    #[Test]
    public function servant_cannot_view_visit_from_different_service_group(): void
    {
        $group1 = ServiceGroup::factory()->create();
        $group2 = ServiceGroup::factory()->create();

        $servant = User::factory()->create([
            'role'             => 'servant',
            'service_group_id' => $group1->id,
        ]);

        $otherBeneficiary = Beneficiary::factory()->create(['service_group_id' => $group2->id]);
        $otherVisit       = Visit::factory()->create(['beneficiary_id' => $otherBeneficiary->id]);

        $this->assertFalse($servant->can('view', $otherVisit));
    }

    #[Test]
    public function only_super_admin_and_service_leader_can_delete_visits(): void
    {
        $group = ServiceGroup::factory()->create();

        $superAdmin    = User::factory()->create(['role' => 'super_admin']);
        $serviceLeader = User::factory()->create(['role' => 'service_leader']);
        $familyLeader  = User::factory()->create(['role' => 'family_leader', 'service_group_id' => $group->id]);
        $servant       = User::factory()->create(['role' => 'servant', 'service_group_id' => $group->id]);

        $beneficiary = Beneficiary::factory()->create(['service_group_id' => $group->id]);
        $visit       = Visit::factory()->create(['beneficiary_id' => $beneficiary->id]);

        $this->assertTrue($superAdmin->can('delete', $visit));
        $this->assertTrue($serviceLeader->can('delete', $visit));
        $this->assertFalse($familyLeader->can('delete', $visit));
        $this->assertFalse($servant->can('delete', $visit));
    }
}
