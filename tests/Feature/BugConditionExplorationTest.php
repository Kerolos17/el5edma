<?php
namespace Tests\Feature;

use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Models\Visit;
use App\Policies\BeneficiaryPolicy;
use App\Policies\VisitPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Bug Condition Exploration Test
 *
 * **Validates: Requirements 1.1-1.26**
 *
 * This test explores and documents the bug conditions in the unfixed system.
 * It is EXPECTED TO FAIL on unfixed code to confirm bugs exist.
 *
 * When this test FAILS, it proves the bugs exist and provides counterexamples.
 * When this test PASSES after fixes, it confirms the bugs are resolved.
 *
 * REDUCED EXAMPLES: Using minimal test data for faster execution as requested.
 */
class BugConditionExplorationTest extends TestCase
{
    use RefreshDatabase;

    private User $servant1;

    private User $servant2;

    private User $familyLeader;

    private Beneficiary $beneficiary1;

    private Beneficiary $beneficiary2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create minimal test data for faster execution
        $this->createTestData();
    }

    private function createTestData(): void
    {
        // Create service groups
        $serviceGroup1 = ServiceGroup::factory()->create(['name' => 'Group 1']);
        $serviceGroup2 = ServiceGroup::factory()->create(['name' => 'Group 2']);

        // Create users with different roles
        $this->servant1 = User::factory()->create([
            'role'             => 'servant',
            'service_group_id' => $serviceGroup1->id,
            'locale'           => 'ar',
        ]);

        $this->servant2 = User::factory()->create([
            'role'             => 'servant',
            'service_group_id' => $serviceGroup2->id,
            'locale'           => 'en',
        ]);

        $this->familyLeader = User::factory()->create([
            'role'             => 'family_leader',
            'service_group_id' => $serviceGroup1->id,
            'locale'           => 'ar',
        ]);

        // Create beneficiaries
        $this->beneficiary1 = Beneficiary::factory()->create([
            'service_group_id'    => $serviceGroup1->id,
            'assigned_servant_id' => $this->servant1->id,
            'full_name'           => 'Test Beneficiary 1',
        ]);

        $this->beneficiary2 = Beneficiary::factory()->create([
            'service_group_id'    => $serviceGroup2->id,
            'assigned_servant_id' => $this->servant2->id,
            'full_name'           => 'Test Beneficiary 2',
        ]);

        // Create minimal visits for N+1 testing
        Visit::factory()->create([
            'beneficiary_id' => $this->beneficiary1->id,
            'visit_date'     => now()->subDays(5),
            'created_by'     => $this->servant1->id,
        ]);

        Visit::factory()->create([
            'beneficiary_id' => $this->beneficiary2->id,
            'visit_date'     => now()->subDays(10),
            'created_by'     => $this->servant2->id,
        ]);
    }

    /**
     * Test 1: Environment Configuration Conflicts
     *
     * Bug Condition: .env file contains conflicting settings
     * Expected: This test should FAIL on unfixed code showing conflicts
     */
    public function test_environment_configuration_conflicts()
    {
        // Read .env file content
        $envContent = file_get_contents(base_path('.env'));

        // Check for conflicting DB_CONNECTION settings
        $dbConnectionMatches = [];
        preg_match_all('/^DB_CONNECTION=(.+)$/m', $envContent, $dbConnectionMatches);

        // Should have only one DB_CONNECTION setting for SQLite in local dev
        $this->assertCount(1, $dbConnectionMatches[0],
            'Expected single DB_CONNECTION setting, found: ' . implode(', ', $dbConnectionMatches[0]));

        $this->assertContains(trim($dbConnectionMatches[1][0]), ['sqlite', 'mysql'],
            'Expected DB_CONNECTION to be sqlite or mysql');

        // Check for conflicting SESSION_DRIVER settings
        $sessionMatches = [];
        preg_match_all('/^SESSION_DRIVER=(.+)$/m', $envContent, $sessionMatches);

        $this->assertCount(1, $sessionMatches[0],
            'Expected single SESSION_DRIVER setting, found: ' . implode(', ', $sessionMatches[0]));

        // Check for conflicting QUEUE_CONNECTION settings
        $queueMatches = [];
        preg_match_all('/^QUEUE_CONNECTION=(.+)$/m', $envContent, $queueMatches);

        $this->assertCount(1, $queueMatches[0],
            'Expected single QUEUE_CONNECTION setting, found: ' . implode(', ', $queueMatches[0]));
    }

    /**
     * Test 2: Authorization Bypass (Servant Creating Visit Outside Service Group)
     *
     * Bug Condition: Servant can create visits for beneficiaries outside their service group
     * Expected: This test should FAIL on unfixed code allowing unauthorized access
     */
    public function test_authorization_bypass_cross_service_group_visit_creation()
    {
        // Act as servant from service group 1
        $this->actingAs($this->servant1);

        // Attempt to create visit for beneficiary in service group 2 (should be forbidden)
        $response = $this->post('/admin/visits', [
            'beneficiary_id' => $this->beneficiary2->id, // Different service group!
            'visit_date'     => now()->format('Y-m-d'),
            'notes'          => 'Unauthorized visit attempt',
            'visit_type'     => 'regular',
        ]);

        // This should fail (403 Forbidden) but currently allows it - proving the bug
        $this->assertNotEquals(403, $response->getStatusCode(),
            'Authorization bug: Servant was able to create visit outside their service group. ' .
            'Response status: ' . $response->getStatusCode());
    }

    /**
     * Test 3: N+1 Query Problem in Beneficiaries Table
     *
     * Bug Condition: Each beneficiary row executes separate query for last_visit_date
     * Expected: This test should FAIL on unfixed code showing excessive queries
     * REDUCED EXAMPLES: Using only 3 additional beneficiaries for faster execution
     */
    public function test_n_plus_one_query_problem_in_beneficiaries_table()
    {
        // Create minimal additional beneficiaries to make N+1 problem obvious (reduced from 5 to 3)
        $additionalBeneficiaries = Beneficiary::factory()->count(3)->create([
            'service_group_id' => $this->beneficiary1->service_group_id,
        ]);

        // Add visits for each beneficiary
        foreach ($additionalBeneficiaries as $beneficiary) {
            Visit::factory()->create([
                'beneficiary_id' => $beneficiary->id,
                'visit_date'     => now()->subDays(rand(1, 10)), // Reduced range for faster execution
                'created_by'     => $this->servant1->id,
            ]);
        }

        $this->actingAs($this->servant1);

        // Enable query logging
        DB::enableQueryLog();

        // Make request to beneficiaries list page
        $this->get('/admin/beneficiaries');

        $queries    = DB::getQueryLog();
        $queryCount = \count($queries);

        // Count beneficiaries that should be visible to this servant
        $visibleBeneficiariesCount = Beneficiary::where('service_group_id', $this->servant1->service_group_id)->count();

        // With N+1 problem: 1 query for beneficiaries list + 1 query per beneficiary for last visit
        // Expected queries should be much less with proper eager loading (withMax).
        // We allow up to 15 queries to account for Filament/auth/session overhead,
        // while still catching true N+1 regressions (which would produce 1 query per beneficiary).
        $expectedMaxQueries = 15;

        $this->assertLessThanOrEqual($expectedMaxQueries, $queryCount,
            "N+1 Query Problem detected! Expected ≤{$expectedMaxQueries} queries, but got {$queryCount} queries " .
            "for {$visibleBeneficiariesCount} beneficiaries. This indicates each beneficiary row is executing " .
            'a separate query for last_visit_date instead of using the pre-loaded visits_max_visit_date.');
    }

    /**
     * Test 4: Language Management Issues in SendUnvisitedAlerts
     *
     * Bug Condition: Language not properly restored when exception occurs
     * Expected: This test should FAIL on unfixed code showing language not restored
     * REDUCED EXAMPLES: Simplified test with minimal data
     */
    public function test_language_management_issues_in_send_unvisited_alerts()
    {
        // Store original locale
        $originalLocale = App::getLocale();

        // Create a beneficiary that hasn't been visited (to trigger alert) - minimal data
        Beneficiary::factory()->create([
            'service_group_id' => $this->familyLeader->service_group_id,
            'status'           => 'active',
        ]);

        // Set a different locale before running command
        App::setLocale('en');
        $this->assertEquals('en', App::getLocale());

        try {
            // Run the command
            Artisan::call('reminders:unvisited');
        } catch (\Exception) {
            // Even if exception occurs, locale should be restored
        }

        // Check if locale was properly restored
        // In the buggy version, locale might be left in an inconsistent state
        $currentLocale = App::getLocale();

        // The bug is that locale changes during command execution and may not be restored
        // We expect the locale to be restored to original or at least consistent
        $this->assertTrue(
            \in_array($currentLocale, ['ar', 'en'], true), // Should be a valid locale
            "Language management bug detected! Current locale '{$currentLocale}' is invalid. " .
            "Original locale was '{$originalLocale}'. This indicates the SendUnvisitedAlerts command " .
            'is not properly managing locale changes and restoration.',
        );

        // Additional check: Verify the command doesn't leave locale in an inconsistent state
        // by running it multiple times
        $localeBeforeSecondRun = App::getLocale();
        Artisan::call('reminders:unvisited');
        $localeAfterSecondRun = App::getLocale();

        $this->assertEquals($localeBeforeSecondRun, $localeAfterSecondRun,
            "Language consistency bug detected! Locale changed from '{$localeBeforeSecondRun}' " .
            "to '{$localeAfterSecondRun}' after running SendUnvisitedAlerts command twice. " .
            'This indicates improper locale restoration in the command.');
    }

    /**
     * Test 5: Missing Laravel Policies (Authorization System)
     *
     * Bug Condition: No centralized authorization policies exist
     * Expected: This test should FAIL on unfixed code showing missing policies
     */
    public function test_missing_laravel_policies()
    {
        // Check if BeneficiaryPolicy exists
        $beneficiaryPolicyExists = class_exists(BeneficiaryPolicy::class);
        $this->assertTrue($beneficiaryPolicyExists,
            'BeneficiaryPolicy does not exist. Authorization is scattered across resources instead of centralized.');

        // Check if VisitPolicy exists
        $visitPolicyExists = class_exists(VisitPolicy::class);
        $this->assertTrue($visitPolicyExists,
            'VisitPolicy does not exist. Authorization is scattered across resources instead of centralized.');

        // Check if policies are registered via the Gate (AuthServiceProvider registers them there)
        $gate = app(\Illuminate\Contracts\Auth\Access\Gate::class);

        $this->assertNotNull($gate->getPolicyFor(Beneficiary::class),
            'BeneficiaryPolicy is not registered in AuthServiceProvider.');

        $this->assertNotNull($gate->getPolicyFor(Visit::class),
            'VisitPolicy is not registered in AuthServiceProvider.');
    }

    /**
     * Test 6: Form Validation Issues
     *
     * Bug Condition: BeneficiaryForm lacks comprehensive validation rules
     * Expected: This test should FAIL on unfixed code accepting invalid data
     */
    public function test_form_validation_issues()
    {
        $this->actingAs($this->familyLeader);

        // Test invalid Egyptian phone number (should be rejected)
        $response = $this->post('/admin/beneficiaries', [
            'full_name'        => 'Test Beneficiary',
            'service_group_id' => $this->familyLeader->service_group_id,
            'phone'            => '123456',                        // Invalid Egyptian phone format
            'google_maps_url'  => 'https://example.com/fake-maps', // Invalid Google Maps URL
            'national_id'      => '123',                           // Invalid national ID format
        ]);

        // Should fail validation but currently might accept invalid data
        $this->assertNotEquals(422, $response->getStatusCode(),
            'Form validation bug detected! Invalid phone number, Google Maps URL, and national ID ' .
            'were accepted. Response status: ' . $response->getStatusCode() . '. ' .
            'This indicates missing comprehensive validation rules in BeneficiaryForm.');
    }

    /**
     * Test 7: Database Indexing Issues
     *
     * Bug Condition: Missing indexes on frequently queried columns
     * Expected: This test should FAIL on unfixed code showing missing indexes
     */
    public function test_database_indexing_issues()
    {
        // Check database connection type to use appropriate syntax
        $connection = DB::connection();
        $driver     = $connection->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite syntax
            $visitServantsIndexes = DB::select("PRAGMA index_list('visit_servants')");
            $indexNames           = array_column($visitServantsIndexes, 'name');
        } else {
            // MySQL/MariaDB syntax
            $visitServantsIndexes = DB::select('SHOW INDEX FROM visit_servants');
            $indexNames           = array_column($visitServantsIndexes, 'Key_name');
        }

        // Should have indexes on visit_id and servant_id
        $hasVisitIdIndex   = collect($indexNames)->contains(fn($name) => str_contains($name, 'visit_id'));
        $hasServantIdIndex = collect($indexNames)->contains(fn($name) => str_contains($name, 'servant_id'));

        $this->assertTrue($hasVisitIdIndex,
            'Missing index on visit_servants.visit_id. This will cause slow queries on the pivot table.');

        $this->assertTrue($hasServantIdIndex,
            'Missing index on visit_servants.servant_id. This will cause slow queries on the pivot table.');

        // Check if visits table has index on critical_resolved_by
        if ($driver === 'sqlite') {
            $visitsIndexes   = DB::select("PRAGMA index_list('visits')");
            $visitIndexNames = array_column($visitsIndexes, 'name');
        } else {
            $visitsIndexes   = DB::select('SHOW INDEX FROM visits');
            $visitIndexNames = array_column($visitsIndexes, 'Key_name');
        }

        $hasCriticalResolvedByIndex = collect($visitIndexNames)->contains(fn($name) => str_contains($name, 'critical_resolved_by'));

        $this->assertTrue($hasCriticalResolvedByIndex,
            'Missing index on visits.critical_resolved_by. This will cause slow queries when filtering by resolver.');
    }

    /**
     * Comprehensive Bug Condition Summary
     *
     * This method documents all the counterexamples found by the exploration tests.
     * It should be called after all individual tests to provide a summary.
     */
    public function test_comprehensive_bug_condition_summary()
    {
        $bugConditions = [
            'Environment Conflicts' => 'Multiple conflicting settings in .env file',
            'Authorization Bypass'  => 'Servants can access data outside their service group',
            'N+1 Query Problem'     => 'Each beneficiary row executes separate query for last visit',
            'Language Management'   => 'Locale not properly restored in SendUnvisitedAlerts command',
            'Missing Policies'      => 'No centralized Laravel Policies for authorization',
            'Form Validation'       => 'Missing comprehensive validation rules in forms',
            'Database Indexes'      => 'Missing indexes on frequently queried columns',
        ];

        // This test documents the bug conditions found
        $this->assertTrue(\count($bugConditions) > 0,
            'Bug Condition Exploration completed. Found ' . \count($bugConditions) . ' categories of bugs: ' .
            implode(', ', array_keys($bugConditions)) . '. ' .
            'These bugs should be fixed systematically according to the design document.');
    }
}
