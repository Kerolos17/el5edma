<?php

// Feature: notifications-optimization, Property 2: اكتمال إنشاء سجلات الإشعارات

namespace Tests\Unit;

use App\Models\Beneficiary;
use App\Models\MinistryNotification;
use App\Models\ScheduledVisit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Property 2: اكتمال إنشاء سجلات الإشعارات
 *
 * Validates: Requirements 3.1
 *
 * After running `reminders:scheduled-visits`, there must be a MinistryNotification
 * record of type `visit_reminder` for every eligible scheduled visit
 * (i.e., each visit with an assignedServant).
 */
class ScheduledVisitNotificationCompletenessPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 2 (scheduled-visits): لأي مجموعة عشوائية من الزيارات المجدولة للغد،
     * بعد تنفيذ reminders:scheduled-visits يوجد سجل MinistryNotification من نوع visit_reminder
     * لكل زيارة مؤهلة (لها assignedServant).
     *
     * Validates: Requirements 3.1
     *
     * 100 iterations with random counts of scheduled visits (1–10),
     * each with status=pending, reminder_sent_at=null, and an assignedServant.
     */
    public function test_scheduled_visits_command_creates_notification_for_every_eligible_visit(): void
    {
        // Feature: notifications-optimization, Property 2: اكتمال إنشاء سجلات الإشعارات

        mt_srand(66666);

        $tomorrow = now()->addDay()->toDateString();

        for ($i = 0; $i < 100; $i++) {
            Queue::fake();

            $n = mt_rand(1, 10);

            // Track expected servant user IDs for this iteration
            $expectedServantIds = [];

            for ($j = 0; $j < $n; $j++) {
                $servant = User::factory()->create([
                    'fcm_token' => "sv_token_{$i}_{$j}",
                    'email' => "sv_servant_{$i}_{$j}@test.com",
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

                $expectedServantIds[] = $servant->id;
            }

            $this->artisan('reminders:scheduled-visits');

            // Assert a MinistryNotification of type visit_reminder exists for each servant
            foreach ($expectedServantIds as $userId) {
                $exists = MinistryNotification::where('user_id', $userId)
                    ->where('type', 'visit_reminder')
                    ->exists();

                $this->assertTrue(
                    $exists,
                    "Iteration {$i}: MinistryNotification (visit_reminder) must exist for user_id={$userId}"
                );
            }

            // Assert total count matches expected servants
            $actualCount = MinistryNotification::whereIn('user_id', $expectedServantIds)
                ->where('type', 'visit_reminder')
                ->count();

            $this->assertSame(
                count($expectedServantIds),
                $actualCount,
                "Iteration {$i}: expected " . count($expectedServantIds) . " visit_reminder notifications, got {$actualCount}"
            );

            // Clean up for next iteration
            MinistryNotification::query()->delete();
            ScheduledVisit::query()->delete();
            Beneficiary::query()->delete();
            User::where('email', 'like', "sv_servant_{$i}_%@test.com")->delete();
        }
    }

    /**
     * Edge case: لا توجد زيارات مجدولة للغد — لا يُنشأ أي سجل إشعار.
     *
     * Validates: Requirements 3.1
     */
    public function test_scheduled_visits_command_creates_no_notifications_when_no_eligible_visits(): void
    {
        Queue::fake();

        $this->artisan('reminders:scheduled-visits');

        $this->assertDatabaseCount('ministry_notifications', 0);
    }

    /**
     * Edge case: زيارة مجدولة بدون assignedServant — لا يُنشأ أي سجل إشعار.
     *
     * Validates: Requirements 3.1
     */
    public function test_scheduled_visits_command_creates_no_notifications_when_visit_has_no_servant(): void
    {
        Queue::fake();

        $tomorrow    = now()->addDay()->toDateString();
        $beneficiary = Beneficiary::factory()->create(['status' => 'active']);
        $creator     = User::factory()->create(['email' => 'creator_no_servant@test.com']);

        ScheduledVisit::create([
            'beneficiary_id'      => $beneficiary->id,
            'assigned_servant_id' => null,
            'scheduled_date'      => $tomorrow,
            'scheduled_time'      => '10:00:00',
            'status'              => 'pending',
            'reminder_sent_at'    => null,
            'created_by'          => $creator->id,
        ]);

        $this->artisan('reminders:scheduled-visits');

        $this->assertDatabaseCount('ministry_notifications', 0);
    }

    /**
     * Edge case: زيارة مجدولة سبق إرسال تذكيرها — لا يُنشأ سجل إشعار جديد.
     *
     * Validates: Requirements 3.1
     */
    public function test_already_reminded_visits_are_not_notified_again(): void
    {
        Queue::fake();

        $tomorrow = now()->addDay()->toDateString();

        $servant = User::factory()->create([
            'fcm_token' => 'already_reminded_token',
            'email'     => 'already_reminded_servant@test.com',
        ]);

        $beneficiary = Beneficiary::factory()->create(['status' => 'active']);

        ScheduledVisit::create([
            'beneficiary_id'      => $beneficiary->id,
            'assigned_servant_id' => $servant->id,
            'scheduled_date'      => $tomorrow,
            'scheduled_time'      => '10:00:00',
            'status'              => 'pending',
            'reminder_sent_at'    => now()->subHour(),
            'created_by'          => $servant->id,
        ]);

        $this->artisan('reminders:scheduled-visits');

        $this->assertDatabaseCount('ministry_notifications', 0);
    }
}
