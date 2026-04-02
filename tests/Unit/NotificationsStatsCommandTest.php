<?php

// Feature: notifications-optimization — Unit tests for notifications:stats command (Requirements 9.3)

namespace Tests\Unit;

use App\Models\MinistryNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for notifications:stats command
 *
 * Requirements: 9.3
 */
class NotificationsStatsCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1: The command outputs a table with the three required columns.
     *
     * Validates: Requirements 9.3 — THE Notification_System SHALL توفير أمر Artisan
     * notifications:stats يعرض إحصائيات النظام: عدد الإشعارات غير المقروءة،
     * عدد tokens النشطة، عدد الإشعارات المرسلة خلال آخر 7 أيام.
     */
    public function test_command_outputs_three_required_columns(): void
    {
        $this->artisan('notifications:stats')
            ->expectsOutputToContain('إشعارات غير مقروءة')
            ->expectsOutputToContain('FCM tokens نشطة')
            ->expectsOutputToContain('إشعارات آخر 7 أيام')
            ->assertSuccessful();
    }

    /**
     * Test 2: The command outputs correct counts for known data.
     *
     * Validates: Requirements 9.3 — the stats values must reflect actual DB state.
     */
    public function test_command_outputs_correct_values(): void
    {
        // Create 2 users with FCM tokens and 1 without
        $userWithToken1 = User::factory()->create(['fcm_token' => 'token-aaa']);
        $userWithToken2 = User::factory()->create(['fcm_token' => 'token-bbb']);
        $userNoToken    = User::factory()->create(['fcm_token' => null]);

        // Create 3 unread notifications (read_at = null)
        MinistryNotification::factory()->count(3)->create([
            'user_id'    => $userWithToken1->id,
            'read_at'    => null,
            'created_at' => now()->subDays(2),
        ]);

        // Create 1 read notification (should NOT count as unread)
        MinistryNotification::factory()->create([
            'user_id'    => $userWithToken1->id,
            'read_at'    => now()->subDay(),
            'created_at' => now()->subDays(3),
        ]);

        // Create 2 notifications older than 7 days (should NOT count in "last 7 days")
        MinistryNotification::factory()->count(2)->create([
            'user_id'    => $userWithToken2->id,
            'read_at'    => null,
            'created_at' => now()->subDays(10),
        ]);

        // Expected counts:
        // unread = 3 (recent unread) + 2 (old unread) = 5
        // fcm tokens = 2 (userWithToken1 + userWithToken2)
        // last 7 days = 3 (recent unread) + 1 (recent read) = 4

        $this->artisan('notifications:stats')
            ->expectsOutputToContain('5') // unread count
            ->expectsOutputToContain('2') // active FCM tokens
            ->expectsOutputToContain('4') // notifications in last 7 days
            ->assertSuccessful();
    }
}
