<?php

namespace Tests\Feature\WebApp;

use App\Livewire\WebApp\ScheduledVisitsPage;
use App\Models\Beneficiary;
use App\Models\ScheduledVisit;
use App\Models\ServiceGroup;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ScheduledVisitsCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_toggle_renders_the_week_grid(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        Livewire::actingAs($user)
            ->test(ScheduledVisitsPage::class)
            ->assertDontSee(__('web_app.calendar.title'))
            ->set('calendarView', true)
            ->assertSee(__('web_app.calendar.title'))
            ->assertSee(__('web_app.calendar.this_week'))
            ->assertSee(now()->startOfWeek(Carbon::SATURDAY)->isoFormat('dddd'));
    }

    public function test_week_navigation_moves_the_range(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        Livewire::actingAs($user)
            ->test(ScheduledVisitsPage::class)
            ->set('calendarView', true)
            ->call('$set', 'weekOffset', 1)
            ->assertSee(now()->startOfWeek(Carbon::SATURDAY)->addWeek()->isoFormat('D MMMM'));
    }

    public function test_calendar_shows_scoped_visits_only(): void
    {
        $group      = ServiceGroup::factory()->create();
        $otherGroup = ServiceGroup::factory()->create();
        $servant    = User::factory()->create(['service_group_id' => $group->id]);

        $mine    = Beneficiary::factory()->create(['full_name' => 'مخدومي', 'service_group_id' => $group->id]);
        $foreign = Beneficiary::factory()->create(['full_name' => 'مخدوم غيري', 'service_group_id' => $otherGroup->id]);

        ScheduledVisit::factory()->create([
            'beneficiary_id'      => $mine->id,
            'assigned_servant_id' => $servant->id,
            'scheduled_date'      => now()->startOfWeek(Carbon::SATURDAY)->toDateString(),
        ]);
        ScheduledVisit::factory()->create([
            'beneficiary_id'      => $foreign->id,
            'assigned_servant_id' => User::factory()->create(['service_group_id' => $otherGroup->id])->id,
            'scheduled_date'      => now()->startOfWeek(Carbon::SATURDAY)->toDateString(),
        ]);

        Livewire::actingAs($servant)
            ->test(ScheduledVisitsPage::class)
            ->set('calendarView', true)
            ->assertSee('مخدومي')
            ->assertDontSee('مخدوم غيري');
    }
}
