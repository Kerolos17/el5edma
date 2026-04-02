<?php

// Feature: notifications-optimization, Property 2: اكتمال إنشاء سجلات الإشعارات

namespace Tests\Unit;

use App\Models\Beneficiary;
use App\Models\MinistryNotification;
use App\Models\ServiceGroup;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Property 2: اكتمال إنشاء سجلات الإشعارات
 *
 * Validates: Requirements 1.3
 *
 * After running `reminders:birthdays`, there must be a MinistryNotification record
 * for every eligible recipient (assignedServant and serviceGroup leader).
 */
class BirthdayNotificationCompletenessPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 2 (birthdays): لأي مجموعة عشوائية من المستفيدين المؤهلين،
     * بعد تنفيذ reminders:birthdays يوجد سجل MinistryNotification لكل مستلم مؤهل.
     *
     * Validates: Requirements 1.3
     *
     * 100 iterations with random counts of eligible beneficiaries (1–10),
     * each with an assignedServant and/or serviceGroup leader.
     */
    public function test_birthday_command_creates_notification_for_every_eligible_recipient(): void
    {
        // Feature: notifications-optimization, Property 2: اكتمال إنشاء سجلات الإشعارات

        mt_srand(42424);

        $targetDate = now()->addDays(3);

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
                        'fcm_token' => "bday_servant_{$i}_{$j}",
                        'email' => "bday_servant_{$i}_{$j}@test.com",
                    ]);
                    $expectedRecipientIds[] = $servant->id;
                }

                if ($hasLeader) {
                    $leader = User::factory()->create([
                        'fcm_token' => "bday_leader_{$i}_{$j}",
                        'email' => "bday_leader_{$i}_{$j}@test.com",
                    ]);
                }

                $serviceGroup = ServiceGroup::factory()->create([
                    'leader_id' => $leader?->id,
                ]);

                if ($hasLeader) {
                    $expectedRecipientIds[] = $leader->id;
                }

                Beneficiary::factory()->create([
                    'status'              => 'active',
                    'birth_date'          => Carbon::create(1990, $targetDate->month, $targetDate->day)->toDateString(),
                    'assigned_servant_id' => $servant?->id,
                    'service_group_id'    => $serviceGroup->id,
                ]);
            }

            $this->artisan('reminders:birthdays');

            // Assert a MinistryNotification record exists for each expected recipient
            foreach ($expectedRecipientIds as $userId) {
                $exists = MinistryNotification::where('user_id', $userId)
                    ->where('type', 'birthday')
                    ->exists();

                $this->assertTrue(
                    $exists,
                    "Iteration {$i}: MinistryNotification must exist for user_id={$userId}"
                );
            }

            // Assert total count matches expected recipients
            $actualCount = MinistryNotification::whereIn('user_id', $expectedRecipientIds)
                ->where('type', 'birthday')
                ->count();

            $this->assertSame(
                count($expectedRecipientIds),
                $actualCount,
                "Iteration {$i}: expected " . count($expectedRecipientIds) . " notifications, got {$actualCount}"
            );

            // Clean up for next iteration
            MinistryNotification::query()->delete();
            Beneficiary::query()->delete();
            ServiceGroup::query()->delete();
            User::where('email', 'like', "bday_%_{$i}_%@test.com")->delete();
        }
    }

    /**
     * Edge case: لا يوجد مستفيدون مؤهلون — لا يُنشأ أي سجل إشعار.
     *
     * Validates: Requirements 1.3
     */
    public function test_birthday_command_creates_no_notifications_when_no_eligible_beneficiaries(): void
    {
        Queue::fake();

        $this->artisan('reminders:birthdays');

        $this->assertDatabaseCount('ministry_notifications', 0);
    }

    /**
     * Edge case: مستفيد بدون خادم معيّن ولا أمين أسرة — لا يُنشأ أي سجل إشعار.
     *
     * Validates: Requirements 1.3
     */
    public function test_birthday_command_creates_no_notifications_when_beneficiary_has_no_recipients(): void
    {
        Queue::fake();

        $targetDate   = now()->addDays(3);
        $serviceGroup = ServiceGroup::factory()->create(['leader_id' => null]);

        Beneficiary::factory()->create([
            'status'              => 'active',
            'birth_date'          => Carbon::create(1990, $targetDate->month, $targetDate->day)->toDateString(),
            'assigned_servant_id' => null,
            'service_group_id'    => $serviceGroup->id,
        ]);

        $this->artisan('reminders:birthdays');

        $this->assertDatabaseCount('ministry_notifications', 0);
    }
}
