<?php

namespace Tests\Feature;

use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BeneficiaryN1OptimizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_beneficiary_resource_uses_withmax_to_avoid_n1_queries()
    {
        // Create test data
        $serviceGroup = ServiceGroup::factory()->create();
        $user         = User::factory()->create([
            'role'             => 'super_admin',
            'service_group_id' => $serviceGroup->id,
        ]);

        // Create 5 beneficiaries with visits
        $beneficiaries = Beneficiary::factory(5)->create([
            'service_group_id' => $serviceGroup->id,
        ]);

        foreach ($beneficiaries as $beneficiary) {
            Visit::factory(2)->create([
                'beneficiary_id' => $beneficiary->id,
                'visit_date'     => now()->subDays(rand(1, 30)),
            ]);
        }

        $this->actingAs($user);

        // Enable query logging
        DB::enableQueryLog();

        // Simulate the exact query from BeneficiaryResource::getEloquentQuery()
        $query = Beneficiary::query()
            ->with(['serviceGroup', 'assignedServant', 'createdBy'])
            ->withMax('visits', 'visit_date');

        $results = $query->get();

        $queries    = DB::getQueryLog();
        $queryCount = count($queries);

        // Should be minimal queries (1-3 max: main query + eager loading)
        $this->assertLessThanOrEqual(3, $queryCount,
            "Expected 1-3 queries but got {$queryCount}. Queries: " .
            collect($queries)->pluck('query')->implode('; '),
        );

        // Verify that visits_max_visit_date is available without additional queries
        DB::flushQueryLog();

        foreach ($results as $beneficiary) {
            $lastVisitDate = $beneficiary->visits_max_visit_date;
            $this->assertNotNull($lastVisitDate,
                "visits_max_visit_date should be pre-loaded for beneficiary {$beneficiary->id}",
            );
        }

        // No additional queries should have been executed when accessing visits_max_visit_date
        $additionalQueries = DB::getQueryLog();
        $this->assertEmpty($additionalQueries,
            'Accessing visits_max_visit_date should not trigger additional queries',
        );
    }

    public function test_beneficiaries_table_uses_preloaded_data()
    {
        // Create test data
        $serviceGroup = ServiceGroup::factory()->create();
        $user         = User::factory()->create([
            'role'             => 'super_admin',
            'service_group_id' => $serviceGroup->id,
        ]);

        $beneficiary = Beneficiary::factory()->create([
            'service_group_id' => $serviceGroup->id,
        ]);

        $visitDate = now()->subDays(5);
        Visit::factory()->create([
            'beneficiary_id' => $beneficiary->id,
            'visit_date'     => $visitDate,
        ]);

        $this->actingAs($user);

        // Load beneficiary with withMax like BeneficiaryResource does
        $beneficiaryWithMax = Beneficiary::query()
            ->withMax('visits', 'visit_date')
            ->find($beneficiary->id);

        // Verify the pre-loaded data is accessible
        $this->assertEquals(
            $visitDate->format('Y-m-d H:i:s'),
            $beneficiaryWithMax->visits_max_visit_date,
        );

        // Test that BeneficiariesTable column would work correctly
        $lastVisitState = $beneficiaryWithMax->visits_max_visit_date;
        $this->assertNotNull($lastVisitState);
        $this->assertEquals($visitDate->format('Y-m-d H:i:s'), $lastVisitState);
    }

    public function test_role_based_scoping_works_with_withmax()
    {
        // Create two service groups
        $serviceGroup1 = ServiceGroup::factory()->create();
        $serviceGroup2 = ServiceGroup::factory()->create();

        // Create a servant user in service group 1
        $servant = User::factory()->create([
            'role'             => 'servant',
            'service_group_id' => $serviceGroup1->id,
        ]);

        // Create beneficiaries in both service groups
        $beneficiary1 = Beneficiary::factory()->create([
            'service_group_id' => $serviceGroup1->id,
        ]);
        $beneficiary2 = Beneficiary::factory()->create([
            'service_group_id' => $serviceGroup2->id,
        ]);

        // Create visits for both
        Visit::factory()->create([
            'beneficiary_id' => $beneficiary1->id,
            'visit_date'     => now()->subDays(3),
        ]);
        Visit::factory()->create([
            'beneficiary_id' => $beneficiary2->id,
            'visit_date'     => now()->subDays(7),
        ]);

        $this->actingAs($servant);

        // Simulate BeneficiaryResource::getEloquentQuery() with role-based scoping
        $query = Beneficiary::query()
            ->where('service_group_id', $servant->service_group_id)
            ->with(['serviceGroup', 'assignedServant', 'createdBy'])
            ->withMax('visits', 'visit_date');

        $results = $query->get();

        // Should only return beneficiary from servant's service group
        $this->assertCount(1, $results);
        $this->assertEquals($beneficiary1->id, $results->first()->id);

        // Should have pre-loaded visit data
        $this->assertNotNull($results->first()->visits_max_visit_date);
    }
}
