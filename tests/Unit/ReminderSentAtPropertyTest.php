<?php

// Feature: notifications-optimization, Property 4: تحديث reminder_sent_at بعد الإرسال

namespace Tests\Unit;

use App\Models\Beneficiary;
use App\Models\ScheduledVisit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Property 4: تحديث reminder_sent_at بعد الإرسال
 *
 * Validates: Requirements 3.3
 *
 * For any set of processed scheduled visits, after running reminders:scheduled-visits,
 * reminder_sent_at must NOT be null for all visits that were processed.
 */
class ReminderSentAtPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 4: لأي مجموعة من الزيارات المجدولة المعالجة، بعد تنفيذ reminders:scheduled-visits
     * يجب أن تكون قيمة reminder_sent_at غير null لجميع الزيارات التي أُرسل لها تذكير.
     *
     * Validates: Requirements 3.3
     *
     * 100 iterations with random counts of scheduled visits (1–10).
     */
    public function test_reminder_sent_at_is_not_null_after_command_runs(): void
    {
        // Feature: notifications-optimization, Property 4: تحديث reminder_sent_at بعد الإرسال

        mt_srand(44444);

        $tomorrow = now()->addDay()->toDateString();

        for ($i = 0; $i < 100; $i++) {
            Queue::fake();

            $count = mt_rand(1, 10);

            $visitIds = [];

            for ($j = 0; $j < $count; $j++) {
                $servant = User::factory()->create([
                    'fcm_token' => "reminder_token_{$i}_{$j}",
                    'email' => "reminder_servant_{$i}_{$j}@example.com",
                ]);

                $beneficiary = Beneficiary::factory()->create([
                    'status' => 'active',
                ]);

                $visit = ScheduledVisit::create([
                    'beneficiary_id'      => $beneficiary->id,
                    'assigned_servant_id' => $servant->id,
                    'scheduled_date'      => $tomorrow,
                    'scheduled_time'      => '10:00:00',
                    'status'              => 'pending',
                    'reminder_sent_at'    => null,
                    'created_by'          => $servant->id,
                ]);

                $visitIds[] = $visit->id;
            }

            $this->artisan('reminders:scheduled-visits');

            // Assert reminder_sent_at is NOT null for all processed visits
            foreach ($visitIds as $visitId) {
                $visit = ScheduledVisit::find($visitId);

                $this->assertNotNull(
                    $visit->reminder_sent_at,
                    "Iteration {$i}: visit_id={$visitId} — reminder_sent_at should not be null after command runs"
                );
            }

            // Clean up for next iteration
            ScheduledVisit::whereIn('id', $visitIds)->delete();
            Beneficiary::query()->delete();
            User::where('email', 'like', "reminder_servant_{$i}_%@example.com")->delete();
        }
    }

    /**
     * Edge case: الزيارات التي لها reminder_sent_at مسبقاً لا تُعالج مرة أخرى.
     *
     * Validates: Requirements 3.3
     */
    public function test_already_sent_visits_are_not_reprocessed(): void
    {
        Queue::fake();

        $tomorrow = now()->addDay()->toDateString();
        $sentAt   = now()->subHour();

        $servant = User::factory()->create([
            'fcm_token' => 'already_sent_token',
            'email'     => 'already_sent_servant@example.com',
        ]);

        $beneficiary = Beneficiary::factory()->create(['status' => 'active']);

        $visit = ScheduledVisit::create([
            'beneficiary_id'      => $beneficiary->id,
            'assigned_servant_id' => $servant->id,
            'scheduled_date'      => $tomorrow,
            'scheduled_time'      => '10:00:00',
            'status'              => 'pending',
            'reminder_sent_at'    => $sentAt,
            'created_by'          => $servant->id,
        ]);

        $this->artisan('reminders:scheduled-visits');

        // reminder_sent_at should remain unchanged (not updated again)
        $visit->refresh();
        $this->assertEquals(
            $sentAt->toDateTimeString(),
            $visit->reminder_sent_at->toDateTimeString(),
            'Already-sent visits should not have their reminder_sent_at updated again'
        );

        Queue::assertNothingPushed();
    }
}
