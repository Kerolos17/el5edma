<?php

namespace Tests\Feature;

use App\Filament\Resources\Beneficiaries\BeneficiaryResource;
use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ServantDataScopeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function servant_eloquent_query_is_scoped_to_their_service_group(): void
    {
        $group1 = ServiceGroup::factory()->create();
        $group2 = ServiceGroup::factory()->create();

        $servant = User::factory()->create([
            'role'             => 'servant',
            'service_group_id' => $group1->id,
        ]);

        $inScope  = Beneficiary::factory()->create(['service_group_id' => $group1->id]);
        $outScope = Beneficiary::factory()->create(['service_group_id' => $group2->id]);

        $this->actingAs($servant);

        $ids = BeneficiaryResource::getEloquentQuery()->pluck('id');

        $this->assertContains($inScope->id, $ids);
        $this->assertNotContains($outScope->id, $ids);
    }

    #[Test]
    public function family_leader_eloquent_query_is_scoped_to_their_service_group(): void
    {
        $group1 = ServiceGroup::factory()->create();
        $group2 = ServiceGroup::factory()->create();

        $leader = User::factory()->create([
            'role'             => 'family_leader',
            'service_group_id' => $group1->id,
        ]);

        $inScope  = Beneficiary::factory()->create(['service_group_id' => $group1->id]);
        $outScope = Beneficiary::factory()->create(['service_group_id' => $group2->id]);

        $this->actingAs($leader);

        $ids = BeneficiaryResource::getEloquentQuery()->pluck('id');

        $this->assertContains($inScope->id, $ids);
        $this->assertNotContains($outScope->id, $ids);
    }

    #[Test]
    public function super_admin_eloquent_query_returns_all_beneficiaries(): void
    {
        $group1 = ServiceGroup::factory()->create();
        $group2 = ServiceGroup::factory()->create();

        $admin = User::factory()->create(['role' => 'super_admin']);

        Beneficiary::factory()->create(['service_group_id' => $group1->id]);
        Beneficiary::factory()->create(['service_group_id' => $group2->id]);

        $this->actingAs($admin);

        $this->assertEquals(2, BeneficiaryResource::getEloquentQuery()->count());
    }

    #[Test]
    public function service_leader_eloquent_query_returns_all_beneficiaries(): void
    {
        $group1 = ServiceGroup::factory()->create();
        $group2 = ServiceGroup::factory()->create();

        $serviceLeader = User::factory()->create(['role' => 'service_leader']);

        Beneficiary::factory()->create(['service_group_id' => $group1->id]);
        Beneficiary::factory()->create(['service_group_id' => $group2->id]);

        $this->actingAs($serviceLeader);

        $this->assertEquals(2, BeneficiaryResource::getEloquentQuery()->count());
    }

    #[Test]
    public function servant_cannot_view_beneficiary_from_different_service_group(): void
    {
        $group1 = ServiceGroup::factory()->create();
        $group2 = ServiceGroup::factory()->create();

        $servant = User::factory()->create([
            'role'             => 'servant',
            'service_group_id' => $group1->id,
        ]);

        $otherBeneficiary = Beneficiary::factory()->create(['service_group_id' => $group2->id]);

        $this->actingAs($servant);

        $this->assertFalse(BeneficiaryResource::canView($otherBeneficiary));
    }

    #[Test]
    public function servant_can_view_beneficiary_in_their_service_group(): void
    {
        $group = ServiceGroup::factory()->create();

        $servant = User::factory()->create([
            'role'             => 'servant',
            'service_group_id' => $group->id,
        ]);

        $beneficiary = Beneficiary::factory()->create(['service_group_id' => $group->id]);

        $this->actingAs($servant);

        $this->assertTrue(BeneficiaryResource::canView($beneficiary));
    }
}
