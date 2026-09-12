<?php

namespace Tests\Unit;

use App\Filament\Widgets\NotificationsBellWidget;
use App\Livewire\Servant\NotificationsBell;
use App\Models\MinistryNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
    public function web_bell_loads_recent_notifications_and_unread_count(): void
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

        $component = new NotificationsBell;
        $component->mount();

        $this->assertEquals(3, $component->unreadCount);
        $this->assertCount(5, $component->notifications);
    }

    #[Test]
    public function web_bell_mark_read_invalidates_cache_and_updates_unread_count(): void
    {
        $notification = MinistryNotification::factory()->create([
            'user_id' => $this->user->id,
            'read_at' => null,
            'data' => [],
        ]);

        Cache::put("notifications_unread_{$this->user->id}", 99, 60);

        $component = new NotificationsBell;
        $component->markRead($notification->id);

        $this->assertEquals(0, $component->unreadCount);
        $this->assertNull(Cache::get("notifications_unread_{$this->user->id}"));
    }

    #[Test]
    public function web_bell_mark_all_read_invalidates_cache_and_updates_unread_count(): void
    {
        MinistryNotification::factory()->count(4)->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        Cache::put("notifications_unread_{$this->user->id}", 99, 60);

        $component = new NotificationsBell;
        $component->markAllRead();

        $this->assertEquals(0, $component->unreadCount);
        $this->assertNull(Cache::get("notifications_unread_{$this->user->id}"));
    }

    #[Test]
    public function web_bell_limits_to_10_records(): void
    {
        MinistryNotification::factory()->count(15)->create([
            'user_id' => $this->user->id,
        ]);

        $component = new NotificationsBell;
        $component->mount();

        $this->assertCount(10, $component->notifications);
    }

    #[Test]
    public function widget_load_notifications_caches_unread_count(): void
    {
        MinistryNotification::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        Cache::flush();

        $widget = new NotificationsBellWidget;
        $widget->mount();

        $this->assertEquals(2, $widget->unreadCount);
        $this->assertEquals(2, Cache::get("notifications_unread_{$this->user->id}"));
    }

    #[Test]
    public function widget_mark_read_clears_and_refreshes_cache(): void
    {
        $notification = MinistryNotification::factory()->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        Cache::put("notifications_unread_{$this->user->id}", 99, 60);

        $widget = new NotificationsBellWidget;
        $widget->markRead($notification->id);

        $this->assertEquals(0, $widget->unreadCount);
        $this->assertEquals(0, Cache::get("notifications_unread_{$this->user->id}"));
    }

    #[Test]
    public function widget_mark_all_read_clears_and_refreshes_cache(): void
    {
        MinistryNotification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        Cache::put("notifications_unread_{$this->user->id}", 99, 60);

        $widget = new NotificationsBellWidget;
        $widget->markAllRead();

        $this->assertEquals(0, $widget->unreadCount);
        $this->assertEquals(0, Cache::get("notifications_unread_{$this->user->id}"));
    }

    #[Test]
    public function widget_limits_to_8_records(): void
    {
        MinistryNotification::factory()->count(15)->create([
            'user_id' => $this->user->id,
        ]);

        $widget = new NotificationsBellWidget;
        $widget->mount();

        $this->assertCount(8, $widget->notifications);
    }
}
