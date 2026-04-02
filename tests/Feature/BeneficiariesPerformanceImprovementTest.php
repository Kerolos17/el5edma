<?php
namespace Tests\Feature;

use App\Filament\Resources\Beneficiaries\BeneficiaryResource;
use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Performance improvement test for N+1 query optimization in BeneficiariesTable
 *
 * **Validates: Requirements 2.7, 2.8**
 *
 * This test measures the performance improvement achieved by the N+1 query fix
 * and verifies that the optimization scales with different numbers of beneficiaries
 * while maintaining data accuracy and working with role-based scoping.
 */
class BeneficiariesPerformanceImprovementTest extends TestCase
{
    use RefreshDatabase;

    private ServiceGroup $serviceGroup1;

    private ServiceGroup $serviceGroup2;

    private User $superAdmin;

    private User $servant;

    private User $familyLeader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestUsers();
    }

    private function createTestUsers(): void
    {
        $this->serviceGroup1 = ServiceGroup::factory()->create(['name' => 'Service Group 1', 'is_active' => true]);
        $this->serviceGroup2 = ServiceGroup::factory()->create(['name' => 'Service Group 2', 'is_active' => true]);

        $this->superAdmin = User::factory()->create([
            'role'      => 'super_admin',
            'locale'    => 'ar',
            'is_active' => true,
        ]);

        $this->servant = User::factory()->create([
            'role'             => 'servant',
            'service_group_id' => $this->serviceGroup1->id,
            'locale'           => 'ar',
            'is_active'        => true,
        ]);

        $this->familyLeader = User::factory()->create([
            'role'             => 'family_leader',
            'service_group_id' => $this->serviceGroup1->id,
            'locale'           => 'ar',
            'is_active'        => true,
        ]);
    }

    /**
     * Test performance improvement with small dataset (10 beneficiaries)
     */
    public function test_performance_improvement_with_small_dataset()
    {
        $beneficiaryCount = 10;
        $this->createBeneficiariesWithVisits($beneficiaryCount);

        $this->actingAs($this->superAdmin);

        // Test optimized query (current implementation)
        $optimizedMetrics = $this->measureOptimizedQuery();

        // Test unoptimized query (simulating old behavior)
        $unoptimizedMetrics = $this->measureUnoptimizedQuery();

        // Verify performance improvement
        $this->assertPerformanceImprovement($optimizedMetrics, $unoptimizedMetrics, $beneficiaryCount);
    }

    /**
     * Test performance improvement with medium dataset (50 beneficiaries)
     */
    public function test_performance_improvement_with_medium_dataset()
    {
        $beneficiaryCount = 50;
        $this->createBeneficiariesWithVisits($beneficiaryCount);

        $this->actingAs($this->superAdmin);

        // Test optimized query (current implementation)
        $optimizedMetrics = $this->measureOptimizedQuery();

        // Test unoptimized query (simulating old behavior)
        $unoptimizedMetrics = $this->measureUnoptimizedQuery();

        // Verify performance improvement scales
        $this->assertPerformanceImprovement($optimizedMetrics, $unoptimizedMetrics, $beneficiaryCount);

        // With more data, the improvement should be more significant
        $queryReduction = $unoptimizedMetrics['queryCount'] - $optimizedMetrics['queryCount'];
        $this->assertGreaterThan(40, $queryReduction,
            "With {$beneficiaryCount} beneficiaries, query reduction should be significant");
    }

    /**
     * Test performance improvement with large dataset (100 beneficiaries)
     */
    public function test_performance_improvement_with_large_dataset()
    {
        $beneficiaryCount = 100;
        $this->createBeneficiariesWithVisits($beneficiaryCount);

        $this->actingAs($this->superAdmin);

        // Test optimized query (current implementation)
        $optimizedMetrics = $this->measureOptimizedQuery();

        // Test unoptimized query (simulating old behavior)
        $unoptimizedMetrics = $this->measureUnoptimizedQuery();

        // Verify performance improvement scales significantly
        $this->assertPerformanceImprovement($optimizedMetrics, $unoptimizedMetrics, $beneficiaryCount);

        // With large dataset, the improvement should be very significant
        $queryReduction = $unoptimizedMetrics['queryCount'] - $optimizedMetrics['queryCount'];
        $this->assertGreaterThan(90, $queryReduction,
            "With {$beneficiaryCount} beneficiaries, query reduction should be very significant");
    }

    /**
     * Test that optimization works with role-based scoping for servant
     */
    public function test_performance_improvement_with_servant_scoping()
    {
        // Create beneficiaries in both service groups
        $this->createBeneficiariesWithVisits(20, $this->serviceGroup1->id);
        $this->createBeneficiariesWithVisits(20, $this->serviceGroup2->id);

        $this->actingAs($this->servant);

        // Test optimized query with servant scoping
        $optimizedMetrics = $this->measureOptimizedQueryWithScoping($this->servant);

        // Test unoptimized query with servant scoping
        $unoptimizedMetrics = $this->measureUnoptimizedQueryWithScoping($this->servant);

        // Verify performance improvement with scoping
        $this->assertPerformanceImprovement($optimizedMetrics, $unoptimizedMetrics, 20);

        // Verify servant only sees their service group's beneficiaries
        $this->assertEquals(20, $optimizedMetrics['resultCount'],
            'Servant should only see beneficiaries from their service group');
    }

    /**
     * Test that optimization works with role-based scoping for family leader
     */
    public function test_performance_improvement_with_family_leader_scoping()
    {
        // Create beneficiaries in both service groups
        $this->createBeneficiariesWithVisits(15, $this->serviceGroup1->id);
        $this->createBeneficiariesWithVisits(15, $this->serviceGroup2->id);

        $this->actingAs($this->familyLeader);

        // Test optimized query with family leader scoping
        $optimizedMetrics = $this->measureOptimizedQueryWithScoping($this->familyLeader);

        // Test unoptimized query with family leader scoping
        $unoptimizedMetrics = $this->measureUnoptimizedQueryWithScoping($this->familyLeader);

        // Verify performance improvement with scoping
        $this->assertPerformanceImprovement($optimizedMetrics, $unoptimizedMetrics, 15);

        // Verify family leader only sees their service group's beneficiaries
        $this->assertEquals(15, $optimizedMetrics['resultCount'],
            'Family leader should only see beneficiaries from their service group');
    }

    /**
     * Test that data accuracy is maintained after optimization
     */
    public function test_data_accuracy_maintained_after_optimization()
    {
        $beneficiaries = $this->createBeneficiariesWithVisits(10);

        $this->actingAs($this->superAdmin);

        // Get results using optimized query
        $optimizedResults = $this->getOptimizedResults();

        // Get results using unoptimized query for comparison
        $unoptimizedResults = $this->getUnoptimizedResults();

        // Verify same number of results
        $this->assertCount(count($unoptimizedResults), $optimizedResults,
            'Optimized and unoptimized queries should return same number of results');

        // Verify data accuracy for each beneficiary
        foreach ($optimizedResults as $index => $optimizedBeneficiary) {
            $unoptimizedBeneficiary = $unoptimizedResults[$index];

            $this->assertEquals($optimizedBeneficiary->id, $unoptimizedBeneficiary->id,
                'Beneficiary IDs should match');

            $this->assertEquals($optimizedBeneficiary->full_name, $unoptimizedBeneficiary->full_name,
                'Beneficiary names should match');

            // Most importantly, verify last visit dates match
            $optimizedLastVisit   = $optimizedBeneficiary->visits_max_visit_date;
            $unoptimizedLastVisit = $unoptimizedBeneficiary->visits()->max('visit_date');

            $this->assertEquals($optimizedLastVisit, $unoptimizedLastVisit,
                "Last visit dates should match for beneficiary {$optimizedBeneficiary->id}");
        }
    }

    /**
     * Test BeneficiariesTable column performance specifically
     */
    public function test_beneficiaries_table_column_performance()
    {
        $beneficiaryCount = 25;
        $this->createBeneficiariesWithVisits($beneficiaryCount);

        $this->actingAs($this->superAdmin);

        // Simulate accessing the table column for all beneficiaries
        DB::enableQueryLog();

        $beneficiaries = BeneficiaryResource::getEloquentQuery()->get();

        // Simulate what the table column does - access visits_max_visit_date for each record
        $lastVisitDates = [];
        foreach ($beneficiaries as $beneficiary) {
            $lastVisitDates[] = $beneficiary->visits_max_visit_date;
        }

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should have minimal queries regardless of beneficiary count
        $this->assertLessThan(10, count($queries),
            'Table column access should use minimal queries. Found ' . count($queries) . " queries for {$beneficiaryCount} beneficiaries");

        // Verify no individual visit queries
        $visitQueries = array_filter($queries, function ($query) {
            return str_contains(strtolower($query['query']), 'select max(`visit_date`) from `visits`');
        });

        $this->assertEmpty($visitQueries,
            'Table column should not trigger individual visit queries');

        // Verify all last visit dates were retrieved
        $this->assertCount($beneficiaryCount, $lastVisitDates,
            'Should retrieve last visit date for all beneficiaries');
    }

    /**
     * Create beneficiaries with visits for testing
     */
    private function createBeneficiariesWithVisits(int $count, ?int $serviceGroupId = null): array
    {
        $serviceGroupId = $serviceGroupId ?? $this->serviceGroup1->id;

        $beneficiaries = Beneficiary::factory($count)->create([
            'service_group_id' => $serviceGroupId,
            'status'           => 'active',
        ]);

        // Create 1-3 visits for each beneficiary with different dates
        foreach ($beneficiaries as $beneficiary) {
            $visitCount = rand(1, 3);
            for ($i = 0; $i < $visitCount; $i++) {
                Visit::factory()->create([
                    'beneficiary_id'     => $beneficiary->id,
                    'visit_date'         => now()->subDays(rand(1, 60)),
                    'created_by'         => $this->superAdmin->id,
                    'type'               => 'home_visit',
                    'beneficiary_status' => 'good',
                ]);
            }
        }

        return $beneficiaries->toArray();
    }

    /**
     * Measure performance of optimized query (current implementation)
     */
    private function measureOptimizedQuery(): array
    {
        DB::enableQueryLog();
        $startTime = microtime(true);

        $results = BeneficiaryResource::getEloquentQuery()->get();

        // Access visits_max_visit_date for each result (simulating table display)
        foreach ($results as $beneficiary) {
            $lastVisit = $beneficiary->visits_max_visit_date;
        }

        $endTime = microtime(true);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        return [
            'queryCount'    => count($queries),
            'executionTime' => $endTime - $startTime,
            'resultCount'   => $results->count(),
            'queries'       => $queries,
        ];
    }

    /**
     * Measure performance of unoptimized query (simulating old behavior)
     */
    private function measureUnoptimizedQuery(): array
    {
        DB::enableQueryLog();
        $startTime = microtime(true);

        // Simulate old behavior: get beneficiaries without withMax
        $results = Beneficiary::query()
            ->with(['serviceGroup', 'assignedServant', 'createdBy'])
            ->get();

        // Simulate old table column behavior: individual query for each beneficiary
        foreach ($results as $beneficiary) {
            $lastVisit = $beneficiary->visits()->max('visit_date');
        }

        $endTime = microtime(true);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        return [
            'queryCount'    => count($queries),
            'executionTime' => $endTime - $startTime,
            'resultCount'   => $results->count(),
            'queries'       => $queries,
        ];
    }

    /**
     * Measure optimized query with role-based scoping
     */
    private function measureOptimizedQueryWithScoping(User $user): array
    {
        DB::enableQueryLog();
        $startTime = microtime(true);

        $query = Beneficiary::query();

        // Apply role-based scoping like BeneficiaryResource does
        if (in_array($user->role, [\App\Enums\UserRole::FamilyLeader, \App\Enums\UserRole::Servant])) {
            $query->where('service_group_id', $user->service_group_id);
        }

        $results = $query->with(['serviceGroup', 'assignedServant', 'createdBy'])
            ->withMax('visits', 'visit_date')
            ->get();

        // Access visits_max_visit_date for each result
        foreach ($results as $beneficiary) {
            $lastVisit = $beneficiary->visits_max_visit_date;
        }

        $endTime = microtime(true);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        return [
            'queryCount'    => count($queries),
            'executionTime' => $endTime - $startTime,
            'resultCount'   => $results->count(),
            'queries'       => $queries,
        ];
    }

    /**
     * Measure unoptimized query with role-based scoping
     */
    private function measureUnoptimizedQueryWithScoping(User $user): array
    {
        DB::enableQueryLog();
        $startTime = microtime(true);

        $query = Beneficiary::query();

        // Apply role-based scoping
        if (in_array($user->role, [\App\Enums\UserRole::FamilyLeader, \App\Enums\UserRole::Servant])) {
            $query->where('service_group_id', $user->service_group_id);
        }

        $results = $query->with(['serviceGroup', 'assignedServant', 'createdBy'])
            ->get();

        // Simulate old behavior: individual query for each beneficiary
        foreach ($results as $beneficiary) {
            $lastVisit = $beneficiary->visits()->max('visit_date');
        }

        $endTime = microtime(true);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        return [
            'queryCount'    => count($queries),
            'executionTime' => $endTime - $startTime,
            'resultCount'   => $results->count(),
            'queries'       => $queries,
        ];
    }

    /**
     * Get results using optimized approach
     */
    private function getOptimizedResults()
    {
        return BeneficiaryResource::getEloquentQuery()
            ->orderBy('id')
            ->get();
    }

    /**
     * Get results using unoptimized approach for comparison
     */
    private function getUnoptimizedResults()
    {
        return Beneficiary::query()
            ->with(['serviceGroup', 'assignedServant', 'createdBy'])
            ->orderBy('id')
            ->get();
    }

    /**
     * Assert that performance improvement is achieved
     */
    private function assertPerformanceImprovement(array $optimized, array $unoptimized, int $beneficiaryCount): void
    {
        // Query count should be significantly reduced
        $this->assertLessThan($unoptimized['queryCount'], $optimized['queryCount'],
            "Optimized query should use fewer queries. Optimized: {$optimized['queryCount']}, Unoptimized: {$unoptimized['queryCount']}");

        // The reduction should be roughly equal to the number of beneficiaries
        // (since each beneficiary would trigger a separate visit query in unoptimized version)
        $queryReduction = $unoptimized['queryCount'] - $optimized['queryCount'];
        $this->assertGreaterThanOrEqual($beneficiaryCount * 0.8, $queryReduction,
            "Query reduction should be approximately equal to beneficiary count. Expected ~{$beneficiaryCount}, got {$queryReduction}");

                                                                 // Execution time should not be dramatically worse (allow up to 10x for small datasets
                                                                 // where timing is inherently variable and overhead can dominate at micro-benchmark scale)
        $timingFloor = max(0.01, $unoptimized['executionTime']); // minimum 10ms floor → threshold always at least 100ms
        $this->assertLessThanOrEqual($timingFloor * 10, $optimized['executionTime'],
            'Optimized query should not be dramatically slower');

        // Same number of results
        $this->assertEquals($unoptimized['resultCount'], $optimized['resultCount'],
            'Both queries should return the same number of results');

                                       // Log performance metrics for documentation
        $this->addToAssertionCount(1); // Prevent risky test warning

        echo "\n=== Performance Improvement Report ===\n";
        echo "Beneficiaries: {$beneficiaryCount}\n";
        echo "Optimized queries: {$optimized['queryCount']}\n";
        echo "Unoptimized queries: {$unoptimized['queryCount']}\n";
        echo "Query reduction: {$queryReduction}\n";
        echo 'Optimized time: ' . number_format($optimized['executionTime'] * 1000, 2) . "ms\n";
        echo 'Unoptimized time: ' . number_format($unoptimized['executionTime'] * 1000, 2) . "ms\n";
        echo 'Time improvement: ' . number_format((1 - $optimized['executionTime'] / $unoptimized['executionTime']) * 100, 1) . "%\n";
        echo "=====================================\n";
    }
}
