<?php

// Feature: notifications-optimization, Property 3: دقة حساب آخر زيارة بدون N+1

namespace Tests\Unit;

use App\Models\Beneficiary;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property 3: دقة حساب آخر زيارة بدون N+1
 *
 * Validates: Requirements 2.1
 *
 * For any set of beneficiaries with their visits, the `visits_max_visit_date` value
 * computed via `withMax()` must equal the `MAX(visit_date)` computed separately
 * for each beneficiary.
 */
class WithMaxAccuracyPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 3: لأي مجموعة من المستفيدين مع زياراتهم،
     * قيمة `visits_max_visit_date` عبر `withMax()` = `MAX(visit_date)` المحسوبة منفصلاً.
     *
     * Validates: Requirements 2.1
     *
     * 100 iterations with random beneficiary counts (1–10) and random visit counts (0–5).
     */
    public function test_with_max_visit_date_matches_separate_max_query(): void
    {
        // Feature: notifications-optimization, Property 3: دقة حساب آخر زيارة بدون N+1

        mt_srand(99999);

        for ($i = 0; $i < 100; $i++) {
            $beneficiaryCount = mt_rand(1, 10);
            $createdIds       = [];

            for ($j = 0; $j < $beneficiaryCount; $j++) {
                $beneficiary = Beneficiary::factory()->create([
                    'status' => 'active',
                ]);
                $createdIds[] = $beneficiary->id;

                $visitCount = mt_rand(0, 5);

                for ($k = 0; $k < $visitCount; $k++) {
                    // Random visit_date within the past 2 years
                    $daysAgo   = mt_rand(1, 730);
                    $visitDate = Carbon::now()->subDays($daysAgo)->format('Y-m-d H:i:s');

                    Visit::factory()->create([
                        'beneficiary_id' => $beneficiary->id,
                        'visit_date'     => $visitDate,
                    ]);
                }
            }

            // ── Query using withMax() ──
            $beneficiaries = Beneficiary::query()
                ->whereIn('id', $createdIds)
                ->withMax('visits', 'visit_date')
                ->get();

            // ── Assert each beneficiary's withMax value matches separate MAX query ──
            foreach ($beneficiaries as $beneficiary) {
                $expectedMax = $beneficiary->visits()->max('visit_date');

                $this->assertSame(
                    $expectedMax,
                    $beneficiary->visits_max_visit_date,
                    "Iteration {$i}, beneficiary {$beneficiary->id}: " .
                    'visits_max_visit_date via withMax() must equal MAX(visit_date) from separate query',
                );
            }

            // Clean up for next iteration
            Visit::whereIn('beneficiary_id', $createdIds)->delete();
            Beneficiary::whereIn('id', $createdIds)->delete();
        }
    }

    /**
     * Edge case: مستفيد بدون زيارات — كلا القيمتين يجب أن تكونا null.
     *
     * Validates: Requirements 2.1
     */
    public function test_with_max_returns_null_for_beneficiary_with_no_visits(): void
    {
        $beneficiary = Beneficiary::factory()->create(['status' => 'active']);

        $result = Beneficiary::query()
            ->where('id', $beneficiary->id)
            ->withMax('visits', 'visit_date')
            ->first();

        $this->assertNull($result->visits_max_visit_date);
        $this->assertNull($beneficiary->visits()->max('visit_date'));
    }

    /**
     * Edge case: مستفيد بزيارة واحدة — القيمة تساوي تاريخ تلك الزيارة.
     *
     * Validates: Requirements 2.1
     */
    public function test_with_max_returns_single_visit_date_when_one_visit(): void
    {
        $beneficiary = Beneficiary::factory()->create(['status' => 'active']);

        $visitDate = '2024-06-15 10:00:00';
        Visit::factory()->create([
            'beneficiary_id' => $beneficiary->id,
            'visit_date'     => $visitDate,
        ]);

        $result = Beneficiary::query()
            ->where('id', $beneficiary->id)
            ->withMax('visits', 'visit_date')
            ->first();

        $this->assertSame(
            $beneficiary->visits()->max('visit_date'),
            $result->visits_max_visit_date,
        );
    }
}
