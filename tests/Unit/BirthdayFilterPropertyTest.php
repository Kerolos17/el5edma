<?php

// Feature: notifications-optimization, Property 1: دقة SQL filter لأعياد الميلاد

namespace Tests\Unit;

use App\Models\Beneficiary;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Property 1: دقة SQL filter لأعياد الميلاد
 *
 * Validates: Requirements 1.1
 *
 * For any set of active beneficiaries with random birth dates, the SQL filter result
 * (MONTH + DAY) must exactly match the PHP filter result on the same data.
 */
class BirthdayFilterPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 1: لأي مجموعة من المستفيدين النشطين بتواريخ ميلاد عشوائية،
     * نتيجة SQL filter (MONTH + DAY) = نتيجة PHP filter على نفس البيانات.
     *
     * Validates: Requirements 1.1
     *
     * 100 iterations with random birth dates and random target dates.
     */
    public function test_sql_birthday_filter_matches_php_filter(): void
    {
        // Feature: notifications-optimization, Property 1: دقة SQL filter لأعياد الميلاد

        mt_srand(12345);

        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $monthExpr = "CAST(strftime('%m', birth_date) AS INTEGER)";
            $dayExpr   = "CAST(strftime('%d', birth_date) AS INTEGER)";
        } else {
            $monthExpr = 'MONTH(birth_date)';
            $dayExpr   = 'DAY(birth_date)';
        }

        for ($i = 0; $i < 100; $i++) {
            // Pick a random target date (any day of the year)
            $targetMonth = mt_rand(1, 12);
            $targetDay   = mt_rand(1, 28); // use 1–28 to avoid invalid dates in all months

            // Create 5–15 active beneficiaries with random birth dates
            $count = mt_rand(5, 15);

            $createdIds = [];

            for ($j = 0; $j < $count; $j++) {
                $month = mt_rand(1, 12);
                $day   = mt_rand(1, 28);
                $year  = mt_rand(1970, 2010);

                $beneficiary = Beneficiary::factory()->create([
                    'status'     => 'active',
                    'birth_date' => Carbon::create($year, $month, $day)->toDateString(),
                ]);

                $createdIds[] = $beneficiary->id;
            }

            // ── SQL filter (same logic as SendBirthdayReminders) ──
            $sqlIds = Beneficiary::query()
                ->where('status', 'active')
                ->whereNotNull('birth_date')
                ->whereRaw("{$monthExpr} = ? AND {$dayExpr} = ?", [
                    $targetMonth,
                    $targetDay,
                ])
                ->whereIn('id', $createdIds)
                ->pluck('id')
                ->sort()
                ->values()
                ->toArray();

            // ── PHP filter (reference implementation) ──
            $phpIds = Beneficiary::query()
                ->where('status', 'active')
                ->whereNotNull('birth_date')
                ->whereIn('id', $createdIds)
                ->get()
                ->filter(fn (Beneficiary $b) => (int) $b->birth_date->month === $targetMonth
                    && (int) $b->birth_date->day                            === $targetDay)
                ->pluck('id')
                ->sort()
                ->values()
                ->toArray();

            $this->assertSame(
                $phpIds,
                $sqlIds,
                "Iteration {$i}: target={$targetMonth}/{$targetDay} — SQL filter result must match PHP filter result",
            );

            // Clean up for next iteration
            Beneficiary::whereIn('id', $createdIds)->delete();
        }
    }

    /**
     * Edge case: لا يوجد مستفيدون نشطون — كلا الفلترين يُرجعان مجموعة فارغة.
     *
     * Validates: Requirements 1.1
     */
    public function test_sql_filter_returns_empty_when_no_active_beneficiaries(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $monthExpr = "CAST(strftime('%m', birth_date) AS INTEGER)";
            $dayExpr   = "CAST(strftime('%d', birth_date) AS INTEGER)";
        } else {
            $monthExpr = 'MONTH(birth_date)';
            $dayExpr   = 'DAY(birth_date)';
        }

        $sqlIds = Beneficiary::query()
            ->where('status', 'active')
            ->whereNotNull('birth_date')
            ->whereRaw("{$monthExpr} = ? AND {$dayExpr} = ?", [6, 15])
            ->pluck('id')
            ->toArray();

        $this->assertSame([], $sqlIds);
    }

    /**
     * Edge case: مستفيدون غير نشطين لا يظهرون في أي من الفلترين.
     *
     * Validates: Requirements 1.1
     */
    public function test_inactive_beneficiaries_excluded_by_both_filters(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $monthExpr = "CAST(strftime('%m', birth_date) AS INTEGER)";
            $dayExpr   = "CAST(strftime('%d', birth_date) AS INTEGER)";
        } else {
            $monthExpr = 'MONTH(birth_date)';
            $dayExpr   = 'DAY(birth_date)';
        }

        $targetMonth = 6;
        $targetDay   = 15;

        // Create inactive beneficiary with matching birth date
        $inactive = Beneficiary::factory()->create([
            'status'     => 'inactive',
            'birth_date' => Carbon::create(1990, $targetMonth, $targetDay)->toDateString(),
        ]);

        $sqlIds = Beneficiary::query()
            ->where('status', 'active')
            ->whereNotNull('birth_date')
            ->whereRaw("{$monthExpr} = ? AND {$dayExpr} = ?", [$targetMonth, $targetDay])
            ->pluck('id')
            ->toArray();

        $phpIds = Beneficiary::query()
            ->where('status', 'active')
            ->whereNotNull('birth_date')
            ->get()
            ->filter(fn (Beneficiary $b) => (int) $b->birth_date->month === $targetMonth && (int) $b->birth_date->day === $targetDay,
            )
            ->pluck('id')
            ->toArray();

        $this->assertSame([], $sqlIds);
        $this->assertSame([], $phpIds);
        $this->assertSame($phpIds, $sqlIds);
    }
}
