<?php

// Feature: notifications-optimization, Property 10: نظافة الجدول بعد Cleanup

namespace Tests\Unit;

use App\Models\MinistryNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property 10: نظافة الجدول بعد Cleanup
 *
 * Validates: Requirements 6.1
 *
 * For any state of the ministry_notifications table, after running notifications:cleanup
 * no read notification older than 30 days should exist.
 */
class CleanupNotificationsPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 10: لأي حالة لجدول ministry_notifications، بعد تنفيذ notifications:cleanup
     * يجب ألا يوجد أي سجل مقروء تاريخه أقدم من 30 يوماً.
     *
     * Validates: Requirements 6.1
     *
     * 100 iterations with random mix of:
     * - N read notifications older than 90 days (should be deleted)
     * - M read notifications newer than 90 days (should be kept)
     * - K unread notifications between 31-179 days old (should be kept)
     */
    public function test_cleanup_removes_all_old_read_notifications(): void
    {
        // Feature: notifications-optimization, Property 10: نظافة الجدول بعد Cleanup

        mt_srand(10101);

        $user = User::factory()->create(['email' => 'cleanup_test@example.com']);

        for ($i = 0; $i < 100; $i++) {
                                 // Random counts for each category
            $n = mt_rand(1, 20); // read + older than 90 days → should be deleted
            $m = mt_rand(0, 10); // read + newer than 90 days → should be kept
            $k = mt_rand(0, 10); // unread + 31-179 days old → should be kept

            // N read notifications older than 90 days
            $daysOld = mt_rand(91, 365);
            MinistryNotification::factory()->count($n)->create([
                'user_id'    => $user->id,
                'read_at'    => now()->subDays($daysOld),
                'created_at' => now()->subDays($daysOld),
            ]);

            // M read notifications newer than 90 days
            $daysRecent = mt_rand(1, 89);
            MinistryNotification::factory()->count($m)->create([
                'user_id'    => $user->id,
                'read_at'    => now()->subDays($daysRecent),
                'created_at' => now()->subDays($daysRecent),
            ]);

            // K unread notifications between 31-179 days old (kept because <180 days)
            $daysUnread = mt_rand(31, 179);
            MinistryNotification::factory()->count($k)->create([
                'user_id'    => $user->id,
                'read_at'    => null,
                'created_at' => now()->subDays($daysUnread),
            ]);

            // Run the cleanup command
            $this->artisan('notifications:cleanup');

            // Assert: no read notification older than 90 days exists
            $remaining = MinistryNotification::whereNotNull('read_at')
                ->where('read_at', '<', now()->subDays(90))
                ->count();

            $this->assertEquals(
                0,
                $remaining,
                "Iteration {$i}: n={$n}, m={$m}, k={$k} — " .
                "Found {$remaining} read notifications older than 90 days after cleanup"
            );

            // Assert: recent read notifications are preserved
            $recentRead = MinistryNotification::whereNotNull('read_at')
                ->where('read_at', '>=', now()->subDays(90))
                ->count();

            $this->assertEquals(
                $m,
                $recentRead,
                "Iteration {$i}: n={$n}, m={$m}, k={$k} — " .
                "Expected {$m} recent read notifications to be kept, found {$recentRead}"
            );

            // Assert: unread old notifications are preserved
            $unreadOld = MinistryNotification::whereNull('read_at')->count();

            $this->assertEquals(
                $k,
                $unreadOld,
                "Iteration {$i}: n={$n}, m={$m}, k={$k} — " .
                "Expected {$k} unread old notifications to be kept, found {$unreadOld}"
            );

            // Clean up for next iteration
            MinistryNotification::where('user_id', $user->id)->delete();
        }
    }
}
