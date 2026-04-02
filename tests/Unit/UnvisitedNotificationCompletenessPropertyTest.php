<?php

// Feature: notifications-optimization, Property 2: اكتمال إنشاء سجلات الإشعارات

namespace Tests\Unit;

use App\Models\Beneficiary;
use App\Models\MinistryNotification;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Property 2: اكتمال إنشاء سجلات الإشعارات
 *
 * Validates: Requirements 2.2
 *
 * After running `reminders:unvisited`, there must be a MinistryNotification record
 * of type `unvisited_alert` for every eligible recipient (serviceGroup leader
 * and/or assignedServant) for each unvisited beneficiary.
 */
class UnvisitedNotificationCompletenessPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 2 (unvisited): لأي مجموعة عشوائية من المستفيدين غير المزارين،
     * بعد تنفيذ reminders:unvisited يوجد سجل MinistryNotification من نوع unvisited_alert
     * لكل مستلم مؤهل (أمين الأسرة و/أو الخادم المسؤول).
     *
     * Validates: Requirements 2.2
     *
     * 100 iterations with random counts of unvisited beneficiaries (1–10),
     * each with a serviceGroup leader and/or assignedServant.
     */
    public function test_unvisited_command_creates_notification_for_every_eligible_recipient(): void
    {
        // Feature: notifications-optimization, Property 2: اكتمال إنشاء سجلات الإشعارات

        mt_srand(55555);

        for ($i = 0; $i < 100; $i++) {
            Queue::fake();

            $n = mt_rand(1, 10);

            // Track expected recipient user IDs for this iteration
            $expectedRecipientIds = [];

            for ($j = 0; $j < $n; $j++) {
                // Randomly decide which recipients this beneficiary has
                $hasServant = (bool) mt_rand(0, 1);
                $hasLeader  = (bool) mt_rand(0, 1);

                // Ensure at least one recipient exists
                if (! $hasServant && ! $hasLeader) {
                    $hasServant = true;
                }

                $servant = null;
                $leader  = null;

                if ($hasServant) {
                    $servant = User::factory()->create([
                        'fcm_token' => "unvisited_servant_{$i}_{$j}",
                        'email' => "unvisited_servant_{$i}_{$j}@test.com",
                    ]);
                    $expectedRecipientIds[] = $servant->id;
                }

                if ($hasLeader) {
                    $leader = User::factory()->create([
                        'fcm_token' => "unvisited_leader_{$i}_{$j}",
                        'email' => "unvisited_leader_{$i}_{$j}@test.com",
                    ]);
                }

                $serviceGroup = ServiceGroup::factory()->create([
                    'leader_id' => $leader?->id,
                ]);

                if ($hasLeader) {
                    $expectedRecipientIds[] = $leader->id;
                }

                // Randomly choose: no visits at all, or last visit > 14 days ago
                $noVisits = (bool) mt_rand(0, 1);

                $beneficiary = Beneficiary::factory()->create([
                    'status'              => 'active',
                    'assigned_servant_id' => $servant?->id,
                    'service_group_id'    => $serviceGroup->id,
                ]);

                if (! $noVisits) {
                    // Last visit was more than 14 days ago (15–60 days ago)
                    $daysAgo = mt_rand(15, 60);
                    Visit::factory()->create([
                        'beneficiary_id' => $beneficiary->id,
                        'visit_date'     => Carbon::now()->subDays($daysAgo)->format('Y-m-d H:i:s'),
                    ]);
                }
            }

            $this->artisan('reminders:unvisited');

            // Assert a MinistryNotification record of type unvisited_alert exists for each expected recipient
            foreach ($expectedRecipientIds as $userId) {
                $exists = MinistryNotification::where('user_id', $userId)
                    ->where('type', 'unvisited_alert')
                    ->exists();

                $this->assertTrue(
                    $exists,
                    "Iteration {$i}: MinistryNotification (unvisited_alert) must exist for user_id={$userId}"
                );
            }

            // Assert total count matches expected recipients
            $actualCount = MinistryNotification::whereIn('user_id', $expectedRecipientIds)
                ->where('type', 'unvisited_alert')
                ->count();

            $this->assertSame(
                count($expectedRecipientIds),
                $actualCount,
                "Iteration {$i}: expected " . count($expectedRecipientIds) . " unvisited_alert notifications, got {$actualCount}"
            );

            // Clean up for next iteration
            Visit::query()->delete();
            MinistryNotification::query()->delete();
            Beneficiary::query()->delete();
            ServiceGroup::query()->delete();
            User::where('email', 'like', "unvisited_%_{$i}_%@test.com")->delete();
        }
    }

    /**
     * Edge case: لا يوجد مستفيدون غير مزارين — لا يُنشأ أي سجل إشعار.
     *
     * Validates: Requirements 2.2
     */
    public function test_unvisited_command_creates_no_notifications_when_no_eligible_beneficiaries(): void
    {
        Queue::fake();

        $this->artisan('reminders:unvisited');

        $this->assertDatabaseCount('ministry_notifications', 0);
    }

    /**
     * Edge case: مستفيد زُير مؤخراً (أقل من 14 يوماً) — لا يُنشأ أي سجل إشعار.
     *
     * Validates: Requirements 2.2
     */
    public function test_unvisited_command_creates_no_notifications_when_beneficiary_visited_recently(): void
    {
        Queue::fake();

        $servant = User::factory()->create([
            'fcm_token' => 'recent_visit_token',
            'email'     => 'recent_servant@test.com',
        ]);

        $serviceGroup = ServiceGroup::factory()->create(['leader_id' => null]);

        $beneficiary = Beneficiary::factory()->create([
            'status'              => 'active',
            'assigned_servant_id' => $servant->id,
            'service_group_id'    => $serviceGroup->id,
        ]);

        // Visit within the last 14 days (e.g. 5 days ago)
        Visit::factory()->create([
            'beneficiary_id' => $beneficiary->id,
            'visit_date'     => Carbon::now()->subDays(5)->format('Y-m-d H:i:s'),
        ]);

        $this->artisan('reminders:unvisited');

        // نتحقق من عدم وجود إشعارات من نوع unvisited_alert فقط
        // (الـ observers قد تُنشئ إشعارات من أنواع أخرى عند إنشاء البيانات)
        $this->assertEquals(
            0,
            MinistryNotification::where('type', 'unvisited_alert')->count(),
            'يجب ألا يُنشئ الـ command إشعارات للمخدومين المزارين مؤخراً',
        );
    }

    /**
     * Edge case: مستفيد بدون خادم معيّن ولا أمين أسرة — لا يُنشأ أي سجل إشعار.
     *
     * Validates: Requirements 2.2
     */
    public function test_unvisited_command_creates_no_notifications_when_beneficiary_has_no_recipients(): void
    {
        Queue::fake();

        $serviceGroup = ServiceGroup::factory()->create(['leader_id' => null]);

        Beneficiary::factory()->create([
            'status'              => 'active',
            'assigned_servant_id' => null,
            'service_group_id'    => $serviceGroup->id,
        ]);

        $this->artisan('reminders:unvisited');

        $this->assertDatabaseCount('ministry_notifications', 0);
    }
}
