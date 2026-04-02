<?php
namespace Tests\Unit;

use App\Filament\Widgets\NotificationsBellWidget;
use App\Livewire\NotificationsBell;
use App\Models\MinistryNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

// Feature: notifications-optimization, Property 8: Cache invalidation عند القراءة
// Feature: notifications-optimization, Property 9: الحد الأقصى لإشعارات Bell_Widget

class NotificationsBellTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Auth::login($this->user);
    }

    /** @test */
    public function load_notifications_uses_single_query_and_caches_unread_count(): void
    {
        MinistryNotification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);
        MinistryNotification::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'read_at' => now(),
        ]);

        Cache::flush();

        $component = new NotificationsBell();
        $component->mount();

        $this->assertEquals(3, $component->unreadCount);
        $this->assertCount(5, $component->notifications);

        // Cache should now hold the value
        $this->assertTrue(Cache::has("notifications_unread_{$this->user->id}"));
        $this->assertEquals(3, Cache::get("notifications_unread_{$this->user->id}"));
    }

    /** @test */
    public function mark_read_clears_cache_and_updates_unread_count(): void
    {
        $notification = MinistryNotification::factory()->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        // Seed cache with stale value
        Cache::put("notifications_unread_{$this->user->id}", 99, 60);

        $component = new NotificationsBell();
        $component->markRead($notification->id);

        // unreadCount should reflect actual DB value (0), not stale cache (99)
        $this->assertEquals(0, $component->unreadCount);
        // Cache should be refreshed with the correct value
        $this->assertEquals(0, Cache::get("notifications_unread_{$this->user->id}"));
    }

    /** @test */
    public function mark_all_read_clears_cache_and_updates_unread_count(): void
    {
        MinistryNotification::factory()->count(4)->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        // Seed cache with stale value
        Cache::put("notifications_unread_{$this->user->id}", 99, 60);

        $component = new NotificationsBell();
        $component->markAllRead();

        // unreadCount should reflect actual DB value (0), not stale cache (99)
        $this->assertEquals(0, $component->unreadCount);
        // Cache should be refreshed with the correct value
        $this->assertEquals(0, Cache::get("notifications_unread_{$this->user->id}"));
    }

    /** @test */
    public function load_notifications_limits_to_10_records(): void
    {
        MinistryNotification::factory()->count(15)->create([
            'user_id' => $this->user->id,
        ]);

        $component = new NotificationsBell();
        $component->mount();

        $this->assertCount(10, $component->notifications);
    }

    /** @test */
    public function widget_load_notifications_uses_single_query_and_caches_unread_count(): void
    {
        MinistryNotification::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        Cache::flush();

        $widget = new NotificationsBellWidget();
        $widget->mount();

        $this->assertEquals(2, $widget->unreadCount);
        $this->assertTrue(Cache::has("notifications_unread_{$this->user->id}"));
    }

    /** @test */
    public function widget_mark_read_clears_cache(): void
    {
        $notification = MinistryNotification::factory()->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        Cache::put("notifications_unread_{$this->user->id}", 99, 60);

        $widget = new NotificationsBellWidget();
        $widget->markRead($notification->id);

        $this->assertEquals(0, $widget->unreadCount);
        // Cache should be refreshed with the correct value (not stale 99)
        $this->assertEquals(0, Cache::get("notifications_unread_{$this->user->id}"));
    }

    /** @test */
    public function widget_mark_all_read_clears_cache(): void
    {
        MinistryNotification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        Cache::put("notifications_unread_{$this->user->id}", 99, 60);

        $widget = new NotificationsBellWidget();
        $widget->markAllRead();

        $this->assertEquals(0, $widget->unreadCount);
        // Cache should be refreshed with the correct value (not stale 99)
        $this->assertEquals(0, Cache::get("notifications_unread_{$this->user->id}"));
    }

    /** @test */
    public function widget_limits_to_10_records(): void
    {
        MinistryNotification::factory()->count(15)->create([
            'user_id' => $this->user->id,
        ]);

        $widget = new NotificationsBellWidget();
        $widget->mount();

        $this->assertCount(10, $widget->notifications);
    }
}
