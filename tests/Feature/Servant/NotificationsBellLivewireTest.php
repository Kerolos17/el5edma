<?php

declare(strict_types=1);

namespace Tests\Feature\Servant;

use App\Livewire\Servant\NotificationsBell;
use App\Models\MinistryNotification;
use App\Models\ServiceGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class NotificationsBellLivewireTest extends TestCase
{
    use RefreshDatabase, CreatesTestUsers;

    #[Test]
    public function notification_bell_shows_own_notifications(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $other   = $this->createServant($group);

        $mine    = MinistryNotification::factory()->create(['user_id' => $servant->id, 'read_at' => null]);
        $notMine = MinistryNotification::factory()->create(['user_id' => $other->id,   'read_at' => null]);

        $component = Livewire::actingAs($servant)
            ->test(NotificationsBell::class);

        $notifications = $component->notifications;

        $ids = array_column($notifications, 'id');

        $this->assertContains($mine->id, $ids, 'Own notification should be visible');
        $this->assertNotContains($notMine->id, $ids, 'Other user notification should not be visible');
    }

    #[Test]
    public function mark_read_updates_single_notification(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        $notif = MinistryNotification::factory()->create(['user_id' => $servant->id, 'read_at' => null]);

        Livewire::actingAs($servant)
            ->test(NotificationsBell::class)
            ->call('markRead', $notif->id);

        $this->assertNotNull($notif->fresh()->read_at);
    }

    #[Test]
    public function mark_all_read_marks_all_own_notifications(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        MinistryNotification::factory()->count(3)->create(['user_id' => $servant->id, 'read_at' => null]);

        Livewire::actingAs($servant)
            ->test(NotificationsBell::class)
            ->call('markAllRead');

        $this->assertEquals(
            0,
            MinistryNotification::where('user_id', $servant->id)->whereNull('read_at')->count()
        );
    }

    #[Test]
    public function servant_cannot_mark_another_users_notification_as_read(): void
    {
        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);
        $other   = $this->createServant($group);

        $notif = MinistryNotification::factory()->create(['user_id' => $other->id, 'read_at' => null]);

        // markRead uses firstOrFail() with a user_id scope, so calling it on another
        // user's notification throws ModelNotFoundException (404). The notification
        // must remain unread regardless of whether an exception is thrown.
        try {
            Livewire::actingAs($servant)
                ->test(NotificationsBell::class)
                ->call('markRead', $notif->id);
        } catch (\Throwable) {
            // Exception is acceptable — the important assertion is below.
        }

        $this->assertNull($notif->fresh()->read_at, 'Notification should remain unread');
    }
}
