<?php

namespace Tests\Feature\Servant;

use App\Livewire\Servant\NotificationsPage;
use App\Models\MinistryNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ServantNotificationsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_servant_sees_only_their_own_notifications(): void
    {
        $servant = User::factory()->create();
        $other   = User::factory()->create();

        MinistryNotification::create([
            'user_id' => $servant->id,
            'type'    => 'servant_registered',
            'title'   => 'إشعار لي',
            'body'    => 'نصي',
        ]);
        MinistryNotification::create([
            'user_id' => $other->id,
            'type'    => 'servant_registered',
            'title'   => 'إشعار غيري',
            'body'    => 'نص آخر',
        ]);

        $this->actingAs($servant)
            ->get(route('servant.notifications'))
            ->assertOk()
            ->assertSee('إشعار لي')
            ->assertDontSee('إشعار غيري');
    }

    public function test_mark_read_and_redirect_uses_safe_internal_paths(): void
    {
        $servant      = User::factory()->create();
        $notification = MinistryNotification::create([
            'user_id' => $servant->id,
            'type'    => 'visit_created',
            'title'   => 'زيارة',
            'body'    => 'نص',
            'data'    => ['url' => '/app/visits'],
        ]);

        Livewire::actingAs($servant)
            ->test(NotificationsPage::class)
            ->call('markReadAndRedirect', $notification->id)
            ->assertRedirect(route('app.visits'));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_mark_all_read_clears_the_unread_count(): void
    {
        $servant = User::factory()->create();
        foreach (['birthday', 'visit_reminder'] as $type) {
            MinistryNotification::create([
                'user_id' => $servant->id,
                'type'    => $type,
                'title'   => 'عنوان',
                'body'    => 'نص',
            ]);
        }

        Livewire::actingAs($servant)
            ->test(NotificationsPage::class)
            ->call('markAllRead');

        $this->assertSame(0, MinistryNotification::where('user_id', $servant->id)->whereNull('read_at')->count());
    }

    public function test_unread_filter_and_search_work(): void
    {
        $servant = User::factory()->create();
        MinistryNotification::create([
            'user_id' => $servant->id,
            'type'    => 'critical_case',
            'title'   => 'حالة حرجة عاجلة',
            'body'    => 'بلاغ حرج عاجل',
        ]);
        MinistryNotification::create([
            'user_id' => $servant->id,
            'type'    => 'servant_registered',
            'title'   => 'عيد ميلاد محفوظ',
            'body'    => 'بلاغ عيد سابق',
            'read_at' => now(),
        ]);

        Livewire::actingAs($servant)
            ->test(NotificationsPage::class)
            ->set('filter', 'unread')
            ->set('search', 'حرجة')
            ->assertOk()
            ->assertSee('بلاغ حرج عاجل')
            ->assertDontSee('بلاغ عيد سابق');

        // The full page also renders fine with the query params applied.
        $this->actingAs($servant)
            ->get(route('servant.notifications', ['filter' => 'unread', 'q' => 'حرجة']))
            ->assertOk()
            ->assertSee('بلاغ حرج عاجل');
    }
}
