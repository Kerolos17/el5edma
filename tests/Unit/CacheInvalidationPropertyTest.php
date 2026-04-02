<?php

// Feature: notifications-optimization, Property 8: Cache invalidation عند القراءة

namespace Tests\Unit;

use App\Filament\Widgets\NotificationsBellWidget;
use App\Livewire\NotificationsBell;
use App\Models\MinistryNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Property 8: Cache invalidation عند القراءة
 *
 * Validates: Requirements 5.2, 5.3
 *
 * For any user, after markRead or markAllRead is called, unreadCount must reflect
 * the actual value from the database (not the stale cached value).
 */
class CacheInvalidationPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 8: لأي مستخدم، بعد تنفيذ markRead أو markAllRead،
     * يجب أن يعكس unreadCount القيمة الفعلية من DB (وليس القيمة المخزنة في Cache).
     *
     * Validates: Requirements 5.2, 5.3
     *
     * 100 iterations with random N unread notifications (1–10) and random action (markRead / markAllRead).
     */
    public function test_cache_is_invalidated_after_mark_read_or_mark_all_read(): void
    {
        // Feature: notifications-optimization, Property 8: Cache invalidation عند القراءة

        mt_srand(88888);

        for ($i = 0; $i < 100; $i++) {
            // Create a fresh user for each iteration
            $user = User::factory()->create([
                'email' => "cache_test_user_{$i}@example.com",
            ]);
            Auth::login($user);

            $cacheKey = "notifications_unread_{$user->id}";

            // Create N unread notifications (random 1–10)
            $n             = mt_rand(1, 10);
            $notifications = MinistryNotification::factory()->count($n)->create([
                'user_id' => $user->id,
                'read_at' => null,
            ]);

            // Seed the cache with a stale/wrong value (e.g. 999)
            $staleValue = 999;
            Cache::put($cacheKey, $staleValue, 60);

            // Randomly choose action: markRead (0) or markAllRead (1)
            $action = mt_rand(0, 1);

            $component = new NotificationsBell();

            if ($action === 0) {
                // markRead: mark one random notification as read
                $targetNotification = $notifications->random();
                $component->markRead($targetNotification->id);

                // Actual DB count after marking one as read
                $actualUnread = MinistryNotification::where('user_id', $user->id)
                    ->whereNull('read_at')
                    ->count();
            } else {
                // markAllRead: mark all notifications as read
                $component->markAllRead();

                // Actual DB count after marking all as read
                $actualUnread = MinistryNotification::where('user_id', $user->id)
                    ->whereNull('read_at')
                    ->count();
            }

            // Assert unreadCount reflects actual DB value, NOT the stale cache value (999)
            $this->assertNotEquals(
                $staleValue,
                $component->unreadCount,
                "Iteration {$i}: unreadCount should not be the stale cache value ({$staleValue})"
            );

            $this->assertEquals(
                $actualUnread,
                $component->unreadCount,
                "Iteration {$i}: unreadCount ({$component->unreadCount}) must equal actual DB count ({$actualUnread}) after " .
                ($action === 0 ? 'markRead' : 'markAllRead')
            );

            // Clean up for next iteration
            MinistryNotification::where('user_id', $user->id)->delete();
            Cache::forget($cacheKey);
            Auth::logout();
        }
    }

    /**
     * Property 8 (Widget variant): نفس الخاصية على NotificationsBellWidget.
     *
     * Validates: Requirements 5.2, 5.3
     *
     * 100 iterations verifying the Widget also invalidates cache correctly.
     */
    public function test_widget_cache_is_invalidated_after_mark_read_or_mark_all_read(): void
    {
        // Feature: notifications-optimization, Property 8: Cache invalidation عند القراءة

        mt_srand(77777);

        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->create([
                'email' => "widget_cache_test_{$i}@example.com",
            ]);
            Auth::login($user);

            $cacheKey = "notifications_unread_{$user->id}";

            // Create N unread notifications (random 1–10)
            $n             = mt_rand(1, 10);
            $notifications = MinistryNotification::factory()->count($n)->create([
                'user_id' => $user->id,
                'read_at' => null,
            ]);

            // Seed the cache with a stale/wrong value
            $staleValue = 999;
            Cache::put($cacheKey, $staleValue, 60);

            $action = mt_rand(0, 1);
            $widget = new NotificationsBellWidget();

            if ($action === 0) {
                $targetNotification = $notifications->random();
                $widget->markRead($targetNotification->id);
            } else {
                $widget->markAllRead();
            }

            $actualUnread = MinistryNotification::where('user_id', $user->id)
                ->whereNull('read_at')
                ->count();

            $this->assertNotEquals(
                $staleValue,
                $widget->unreadCount,
                "Iteration {$i} (Widget): unreadCount should not be the stale cache value ({$staleValue})"
            );

            $this->assertEquals(
                $actualUnread,
                $widget->unreadCount,
                "Iteration {$i} (Widget): unreadCount ({$widget->unreadCount}) must equal actual DB count ({$actualUnread})"
            );

            // Clean up
            MinistryNotification::where('user_id', $user->id)->delete();
            Cache::forget($cacheKey);
            Auth::logout();
        }
    }
}
