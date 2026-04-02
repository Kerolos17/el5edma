<?php

// Feature: notifications-optimization, Property 11: إضافة Job إلى Queue عند تنفيذ Commands

namespace Tests\Unit;

use App\Jobs\SendFcmNotificationJob;
use App\Models\Beneficiary;
use App\Models\ScheduledVisit;
use App\Models\ServiceGroup;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Property 11: إضافة Job إلى Queue عند تنفيذ Commands
 *
 * Validates: Requirements 8.2
 *
 * For any execution of any of the three Commands (birthdays / unvisited / scheduled-visits)
 * with eligible recipients present, at least one SendFcmNotificationJob must be dispatched
 * to the Queue.
 */
class CommandJobDispatchPropertyTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────────────────────────────────
    // Property 11 — reminders:birthdays
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Property 11 (birthdays): لأي عدد عشوائي من المستفيدين الذين يصادف عيد ميلادهم
     * بعد 3 أيام، يجب أن يُضاف SendFcmNotificationJob واحد على الأقل إلى Queue.
     *
     * Validates: Requirements 8.2
     *
     * 100 iterations with random counts of eligible beneficiaries (1–10).
     */
    public function test_birthday_command_dispatches_job_when_eligible_recipients_exist(): void
    {
        // Feature: notifications-optimization, Property 11: إضافة Job إلى Queue عند تنفيذ Commands

        mt_srand(11111);

        for ($i = 0; $i < 100; $i++) {
            Queue::fake();

            $count = mt_rand(1, 10);

            $targetDate = now()->addDays(3);

            // Create a servant with an FCM token for each beneficiary
            for ($j = 0; $j < $count; $j++) {
                $servant = User::factory()->create([
                    'fcm_token' => "birthday_token_{$i}_{$j}",
                    'email' => "birthday_servant_{$i}_{$j}@example.com",
                ]);

                $serviceGroup = ServiceGroup::factory()->create();

                Beneficiary::factory()->create([
                    'status'              => 'active',
                    'birth_date'          => Carbon::create(1990, $targetDate->month, $targetDate->day),
                    'assigned_servant_id' => $servant->id,
                    'service_group_id'    => $serviceGroup->id,
                ]);
            }

            $this->artisan('reminders:birthdays');

            $this->assertTrue(
                Queue::pushed(SendFcmNotificationJob::class)->isNotEmpty(),
                "Iteration {$i}: count={$count} — reminders:birthdays should dispatch at least one SendFcmNotificationJob"
            );

            // Clean up for next iteration
            Beneficiary::query()->delete();
            User::where('email', 'like', "birthday_servant_{$i}_%@example.com")->delete();
        }
    }

    /**
     * Edge case (birthdays): لا يُضاف أي Job عندما لا يوجد مستفيدون مؤهلون.
     *
     * Validates: Requirements 8.2
     */
    public function test_birthday_command_does_not_dispatch_job_when_no_eligible_recipients(): void
    {
        Queue::fake();

        // No beneficiaries with birthdays in 3 days
        $this->artisan('reminders:birthdays');

        Queue::assertNothingPushed();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Property 11 — reminders:unvisited
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Property 11 (unvisited): لأي عدد عشوائي من المستفيدين الذين لم يُزاروا منذ أكثر من 14 يوماً،
     * يجب أن يُضاف SendFcmNotificationJob واحد على الأقل إلى Queue.
     *
     * Validates: Requirements 8.2
     *
     * 100 iterations with random counts of unvisited beneficiaries (1–10).
     */
    public function test_unvisited_command_dispatches_job_when_eligible_recipients_exist(): void
    {
        // Feature: notifications-optimization, Property 11: إضافة Job إلى Queue عند تنفيذ Commands

        mt_srand(22222);

        for ($i = 0; $i < 100; $i++) {
            Queue::fake();

            $count = mt_rand(1, 10);

            for ($j = 0; $j < $count; $j++) {
                $leader = User::factory()->create([
                    'fcm_token' => "unvisited_token_{$i}_{$j}",
                    'email' => "unvisited_leader_{$i}_{$j}@example.com",
                ]);

                $serviceGroup = ServiceGroup::factory()->create([
                    'leader_id' => $leader->id,
                ]);

                Beneficiary::factory()->create([
                    'status'           => 'active',
                    'service_group_id' => $serviceGroup->id,
                    // No visits → never visited → eligible
                ]);
            }

            $this->artisan('reminders:unvisited');

            $this->assertTrue(
                Queue::pushed(SendFcmNotificationJob::class)->isNotEmpty(),
                "Iteration {$i}: count={$count} — reminders:unvisited should dispatch at least one SendFcmNotificationJob"
            );

            // Clean up for next iteration
            Beneficiary::query()->delete();
            ServiceGroup::whereIn('leader_id', User::where('email', 'like', "unvisited_leader_{$i}_%@example.com")->pluck('id'))->delete();
            User::where('email', 'like', "unvisited_leader_{$i}_%@example.com")->delete();
        }
    }

    /**
     * Edge case (unvisited): لا يُضاف أي Job عندما لا يوجد مستفيدون غير مزارين.
     *
     * Validates: Requirements 8.2
     */
    public function test_unvisited_command_does_not_dispatch_job_when_no_eligible_recipients(): void
    {
        Queue::fake();

        // No beneficiaries at all
        $this->artisan('reminders:unvisited');

        Queue::assertNothingPushed();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Property 11 — reminders:scheduled-visits
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Property 11 (scheduled-visits): لأي عدد عشوائي من الزيارات المجدولة للغد،
     * يجب أن يُضاف SendFcmNotificationJob واحد على الأقل إلى Queue.
     *
     * Validates: Requirements 8.2
     *
     * 100 iterations with random counts of scheduled visits (1–10).
     */
    public function test_scheduled_visits_command_dispatches_job_when_eligible_recipients_exist(): void
    {
        // Feature: notifications-optimization, Property 11: إضافة Job إلى Queue عند تنفيذ Commands

        mt_srand(33333);

        for ($i = 0; $i < 100; $i++) {
            Queue::fake();

            $count = mt_rand(1, 10);

            $tomorrow = now()->addDay()->toDateString();

            for ($j = 0; $j < $count; $j++) {
                $servant = User::factory()->create([
                    'fcm_token' => "scheduled_token_{$i}_{$j}",
                    'email' => "scheduled_servant_{$i}_{$j}@example.com",
                ]);

                $beneficiary = Beneficiary::factory()->create([
                    'status' => 'active',
                ]);

                ScheduledVisit::create([
                    'beneficiary_id'      => $beneficiary->id,
                    'assigned_servant_id' => $servant->id,
                    'scheduled_date'      => $tomorrow,
                    'scheduled_time'      => '10:00:00',
                    'status'              => 'pending',
                    'reminder_sent_at'    => null,
                    'created_by'          => $servant->id,
                ]);
            }

            $this->artisan('reminders:scheduled-visits');

            $this->assertTrue(
                Queue::pushed(SendFcmNotificationJob::class)->isNotEmpty(),
                "Iteration {$i}: count={$count} — reminders:scheduled-visits should dispatch at least one SendFcmNotificationJob"
            );

            // Clean up for next iteration
            ScheduledVisit::query()->delete();
            Beneficiary::query()->delete();
            User::where('email', 'like', "scheduled_servant_{$i}_%@example.com")->delete();
        }
    }

    /**
     * Edge case (scheduled-visits): لا يُضاف أي Job عندما لا توجد زيارات مجدولة للغد.
     *
     * Validates: Requirements 8.2
     */
    public function test_scheduled_visits_command_does_not_dispatch_job_when_no_eligible_visits(): void
    {
        Queue::fake();

        // No scheduled visits for tomorrow
        $this->artisan('reminders:scheduled-visits');

        Queue::assertNothingPushed();
    }
}
