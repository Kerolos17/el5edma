<?php

namespace Tests\Feature;

use App\Filament\Resources\Beneficiaries\BeneficiaryResource;
use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Test to verify that BeneficiariesTable uses pre-loaded data instead of N+1 queries
 *
 * **Validates: Requirements 1.7, 2.7**
 *
 * This test ensures that the BeneficiariesTable optimization is working correctly
 * by verifying that visits_max_visit_date is used instead of separate queries.
 */
class BeneficiariesTableOptimizationTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private ServiceGroup $serviceGroup;

    private Beneficiary $beneficiary1;

    private Beneficiary $beneficiary2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestData();
    }

    private function createTestData(): void
    {
        $this->serviceGroup = ServiceGroup::factory()->create(['name' => 'Test Group', 'is_active' => true]);

        $this->superAdmin = User::factory()->create([
            'role'      => 'super_admin',
            'locale'    => 'ar',
            'is_active' => true,
        ]);

        $this->beneficiary1 = Beneficiary::factory()->create([
            'service_group_id' => $this->serviceGroup->id,
            'status'           => 'active',
            'full_name'        => 'Test Beneficiary 1',
        ]);

        $this->beneficiary2 = Beneficiary::factory()->create([
            'service_group_id' => $this->serviceGroup->id,
            'status'           => 'active',
            'full_name'        => 'Test Beneficiary 2',
        ]);

        // Create visits with different dates
        Visit::factory()->create([
            'beneficiary_id'     => $this->beneficiary1->id,
            'visit_date'         => now()->subDays(5),
            'created_by'         => $this->superAdmin->id,
            'type'               => 'home_visit',
            'beneficiary_status' => 'good',
        ]);

        Visit::factory()->create([
            'beneficiary_id'     => $this->beneficiary1->id,
            'visit_date'         => now()->subDays(10), // Older visit
            'created_by'         => $this->superAdmin->id,
            'type'               => 'home_visit',
            'beneficiary_status' => 'good',
        ]);

        Visit::factory()->create([
            'beneficiary_id'     => $this->beneficiary2->id,
            'visit_date'         => now()->subDays(15),
            'created_by'         => $this->superAdmin->id,
            'type'               => 'home_visit',
            'beneficiary_status' => 'good',
        ]);
    }

    /**
     * Test that BeneficiaryResource loads visits_max_visit_date correctly
     */
    public function test_beneficiary_resource_loads_visits_max_visit_date()
    {
        $this->actingAs($this->superAdmin);

        // Get beneficiaries using the same query as BeneficiaryResource
        $beneficiaries = BeneficiaryResource::getEloquentQuery()->get();

        $this->assertCount(2, $beneficiaries);

        // Check that visits_max_visit_date is loaded
        foreach ($beneficiaries as $beneficiary) {
            $this->assertNotNull($beneficiary->visits_max_visit_date,
                "visits_max_visit_date should be loaded for beneficiary {$beneficiary->id}");
        }

        // Verify the correct max dates are loaded
        $beneficiary1 = $beneficiaries->where('id', $this->beneficiary1->id)->first();
        $beneficiary2 = $beneficiaries->where('id', $this->beneficiary2->id)->first();

        $this->assertEquals(
            now()->subDays(5)->format('Y-m-d'),
            Carbon::parse($beneficiary1->visits_max_visit_date)->format('Y-m-d'),
            'Beneficiary 1 should have the most recent visit date (5 days ago)',
        );

        $this->assertEquals(
            now()->subDays(15)->format('Y-m-d'),
            Carbon::parse($beneficiary2->visits_max_visit_date)->format('Y-m-d'),
            'Beneficiary 2 should have the visit date (15 days ago)',
        );
    }

    /**
     * Test that the table column uses pre-loaded data instead of separate queries
     */
    public function test_table_column_uses_preloaded_data()
    {
        $this->actingAs($this->superAdmin);

        // Get beneficiaries with pre-loaded data
        $beneficiaries = BeneficiaryResource::getEloquentQuery()->get();

        $beneficiary1 = $beneficiaries->where('id', $this->beneficiary1->id)->first();
        $beneficiary2 = $beneficiaries->where('id', $this->beneficiary2->id)->first();

        // Test that visits_max_visit_date contains the expected values
        $this->assertNotNull($beneficiary1->visits_max_visit_date);
        $this->assertNotNull($beneficiary2->visits_max_visit_date);

        // Verify the dates match what we expect
        $this->assertEquals(
            now()->subDays(5)->format('Y-m-d'),
            Carbon::parse($beneficiary1->visits_max_visit_date)->format('Y-m-d'),
        );

        $this->assertEquals(
            now()->subDays(15)->format('Y-m-d'),
            Carbon::parse($beneficiary2->visits_max_visit_date)->format('Y-m-d'),
        );

        // Test that the data is accessible without additional queries
        // This simulates what the table column does
        $lastVisitDate1 = $beneficiary1->visits_max_visit_date;
        $lastVisitDate2 = $beneficiary2->visits_max_visit_date;

        $this->assertNotNull($lastVisitDate1);
        $this->assertNotNull($lastVisitDate2);
    }

    /**
     * Test that query count is optimized when loading beneficiaries
     */
    public function test_query_count_is_optimized()
    {
        $this->actingAs($this->superAdmin);

        // Enable query logging
        DB::enableQueryLog();

        // Get beneficiaries using the resource query (which includes withMax)
        $beneficiaries = BeneficiaryResource::getEloquentQuery()->get();

        // Access the pre-loaded visits_max_visit_date for each beneficiary
        foreach ($beneficiaries as $beneficiary) {
            $lastVisitDate = $beneficiary->visits_max_visit_date;
            // This should not trigger additional queries since data is pre-loaded
        }

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // The query count should be minimal:
        // 1. Main beneficiaries query with withMax
        // 2. Possibly some additional queries for relationships (serviceGroup, assignedServant, etc.)
        // But NOT one query per beneficiary for visits

        $this->assertLessThan(10, count($queries),
            'Query count should be minimal when using pre-loaded data. Found ' . count($queries) . ' queries.');

        // Verify no queries contain individual visits lookups
        $visitQueries = array_filter($queries, fn ($query) => str_contains(strtolower($query['query']), 'select max(`visit_date`) from `visits`'));

        $this->assertEmpty($visitQueries,
            'Should not have individual MAX(visit_date) queries when using pre-loaded data');
    }

    /**
     * Test that beneficiaries without visits handle null visits_max_visit_date correctly
     */
    public function test_beneficiaries_without_visits_handle_null_correctly()
    {
        // Create a beneficiary without visits
        $beneficiaryWithoutVisits = Beneficiary::factory()->create([
            'service_group_id' => $this->serviceGroup->id,
            'status'           => 'active',
            'full_name'        => 'Beneficiary Without Visits',
        ]);

        $this->actingAs($this->superAdmin);

        // Get beneficiaries using the resource query
        $beneficiaries = BeneficiaryResource::getEloquentQuery()->get();

        $beneficiaryWithoutVisitsLoaded = $beneficiaries->where('id', $beneficiaryWithoutVisits->id)->first();

        // Should handle null visits_max_visit_date gracefully
        $this->assertNull($beneficiaryWithoutVisitsLoaded->visits_max_visit_date,
            'Beneficiary without visits should have null visits_max_visit_date');

        // Test that the table column handles null values correctly
        // This simulates the getStateUsing callback in the table
        $lastVisitDate = $beneficiaryWithoutVisitsLoaded->visits_max_visit_date;
        $this->assertNull($lastVisitDate);
    }
}
