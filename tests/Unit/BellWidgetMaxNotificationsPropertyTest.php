<?php

// Feature: notifications-optimization, Property 9: الحد الأقصى لإشعارات Bell_Widget

namespace Tests\Unit;

use App\Filament\Widgets\NotificationsBellWidget;
use App\Models\MinistryNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Property 9: الحد الأقصى لإشعارات Bell_Widget
 *
 * Validates: Requirements 5.4
 *
 * For any user with more than 10 notifications, the Widget must return exactly 10 records.
 */
class BellWidgetMaxNotificationsPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 9: لأي مستخدم لديه أكثر من 10 إشعارات،
     * يجب أن يُرجع NotificationsBellWidget 10 سجلات بالضبط.
     *
     * Validates: Requirements 5.4
     *
     * 100 iterations with random N notifications (11–50) per user.
     */
    public function test_bell_widget_returns_exactly_10_notifications_when_user_has_more_than_10(): void
    {
        // Feature: notifications-optimization, Property 9: الحد الأقصى لإشعارات Bell_Widget

        mt_srand(99999);

        for ($i = 0; $i < 100; $i++) {
            // Create a fresh user for each iteration
            $user = User::factory()->create([
                'email' => "bell_widget_max_test_{$i}@example.com",
            ]);
            Auth::login($user);

            // Create N notifications (random 11–50)
            $n = mt_rand(11, 50);
            MinistryNotification::factory()->count($n)->create([
                'user_id' => $user->id,
            ]);

            // Clear cache to ensure fresh load
            Cache::forget("notifications_unread_{$user->id}");

            // Mount the widget (calls loadNotifications internally)
            $component = new NotificationsBellWidget();
            $component->mount();

            $this->assertCount(
                10,
                $component->notifications,
                "Iteration {$i}: n={$n} — Widget should return exactly 10 notifications, got " . count($component->notifications)
            );

            // Clean up for next iteration
            MinistryNotification::where('user_id', $user->id)->delete();
            Cache::forget("notifications_unread_{$user->id}");
            Auth::logout();
        }
    }
}
