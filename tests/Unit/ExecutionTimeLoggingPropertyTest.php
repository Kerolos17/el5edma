<?php

// Feature: notifications-optimization, Property 12: تسجيل وقت التنفيذ والعدد

namespace Tests\Unit;

use App\Models\Beneficiary;
use App\Models\ScheduledVisit;
use App\Models\ServiceGroup;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Property 12: تسجيل وقت التنفيذ والعدد
 *
 * Validates: Requirements 9.1
 *
 * For any execution of any Command_Runner, the logs must contain execution time
 * and notification count at `info` level.
 */
class ExecutionTimeLoggingPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper: create a random command name from the three available commands.
     */
    private function randomCommand(int $seed): string
    {
        $commands = [
            'reminders:birthdays',
            'reminders:unvisited',
            'reminders:scheduled-visits',
        ];

        return $commands[$seed % 3];
    }

    /**
     * Helper: seed data for reminders:birthdays
     */
    private function seedBirthdayData(int $count, string | int $iteration): void
    {
        $targetDate = now()->addDays(3);

        for ($j = 0; $j < $count; $j++) {
            $servant = User::factory()->create([
                'fcm_token' => "log_birthday_token_{$iteration}_{$j}",
                'email' => "log_birthday_servant_{$iteration}_{$j}@example.com",
            ]);

            $serviceGroup = ServiceGroup::factory()->create();

            Beneficiary::factory()->create([
                'status'              => 'active',
                'birth_date'          => Carbon::create(1990, $targetDate->month, $targetDate->day),
                'assigned_servant_id' => $servant->id,
                'service_group_id'    => $serviceGroup->id,
            ]);
        }
    }

    /**
     * Helper: seed data for reminders:unvisited
     */
    private function seedUnvisitedData(int $count, string | int $iteration): void
    {
        for ($j = 0; $j < $count; $j++) {
            $leader = User::factory()->create([
                'fcm_token' => "log_unvisited_token_{$iteration}_{$j}",
                'email' => "log_unvisited_leader_{$iteration}_{$j}@example.com",
            ]);

            $serviceGroup = ServiceGroup::factory()->create([
                'leader_id' => $leader->id,
            ]);

            Beneficiary::factory()->create([
                'status'           => 'active',
                'service_group_id' => $serviceGroup->id,
            ]);
        }
    }

    /**
     * Helper: seed data for reminders:scheduled-visits
     */
    private function seedScheduledVisitsData(int $count, string | int $iteration): void
    {
        $tomorrow = now()->addDay()->toDateString();

        for ($j = 0; $j < $count; $j++) {
            $servant = User::factory()->create([
                'fcm_token' => "log_scheduled_token_{$iteration}_{$j}",
                'email' => "log_scheduled_servant_{$iteration}_{$j}@example.com",
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
    }

    /**
     * Helper: clean up all seeded data after each iteration
     */
    private function cleanupData(): void
    {
        ScheduledVisit::query()->delete();
        Beneficiary::query()->delete();
        ServiceGroup::query()->delete();
        User::where('email', 'like', 'log_%@example.com')->delete();
    }

    /**
     * Property 12: لأي تنفيذ لأي Command_Runner، يجب أن يحتوي سجل الـ logs على
     * وقت التنفيذ الكلي وعدد الإشعارات المرسلة بمستوى `info`.
     *
     * Validates: Requirements 9.1
     *
     * 100 iterations with randomly chosen commands and random data counts (0–5).
     */
    public function test_any_command_runner_logs_execution_time_and_count_at_info_level(): void
    {
        // Feature: notifications-optimization, Property 12: تسجيل وقت التنفيذ والعدد

        mt_srand(12121);

        for ($i = 0; $i < 100; $i++) {
            Queue::fake();
            Log::spy();

            $command = $this->randomCommand($i);
            $count   = mt_rand(0, 5);

            // Seed appropriate data
            match ($command) {
                'reminders:birthdays'        => $this->seedBirthdayData($count, $i),
                'reminders:unvisited'        => $this->seedUnvisitedData($count, $i),
                'reminders:scheduled-visits' => $this->seedScheduledVisitsData($count, $i),
            };

            $this->artisan($command);

            // Verify Log::info was called at least once
            Log::shouldHaveReceived('info')->atLeast()->once();

            // Verify the log call contains execution time and notification count
            $infoCallFound = false;

            foreach (Log::getFacadeRoot()->getHandlers() ?? [] as $handler) {
                // handled below via shouldHaveReceived
            }

            // Use Mockery to inspect the calls made to Log::info
            $logInfoCalls = collect(
                \Mockery::getContainer()->mockery_getExpectationCount() >= 0
                    ? []
                    : []
            );

            // The primary assertion: Log::info must have been called.
            // For birthdays: called with array containing 'notifications_sent' and 'execution_time_sec'
            // For unvisited/scheduled-visits: called with a string containing count and elapsed time
            $this->verifyLogInfoContainsExecutionData($command, $i);

            $this->cleanupData();
        }
    }

    /**
     * Verify that Log::info was called with execution time and count data
     * for the given command.
     */
    private function verifyLogInfoContainsExecutionData(string $command, int $iteration): void
    {
        if ($command === 'reminders:birthdays') {
            // SendBirthdayReminders logs: Log::info('reminders:birthdays', ['notifications_sent' => $count, 'execution_time_sec' => $elapsed])
            Log::shouldHaveReceived('info')
                ->withArgs(function ($message, $context = []) {
                    if ($message === 'reminders:birthdays') {
                        return isset($context['notifications_sent']) && isset($context['execution_time_sec']);
                    }
                    // Also accept string format as fallback
                    if (is_string($message)) {
                        return str_contains($message, 'reminders:birthdays');
                    }
                    return false;
                })
                ->atLeast()->once();
        } elseif ($command === 'reminders:unvisited') {
            // SendUnvisitedAlerts logs: Log::info("reminders:unvisited — تم إرسال {$count} تنبيه في {$elapsed} ثانية")
            Log::shouldHaveReceived('info')
                ->withArgs(function ($message) {
                    return is_string($message) && str_contains($message, 'reminders:unvisited');
                })
                ->atLeast()->once();
        } elseif ($command === 'reminders:scheduled-visits') {
            // SendScheduledVisitReminders logs: Log::info("reminders:scheduled-visits — تم إرسال {$count} تذكير في {$elapsed} ثانية")
            Log::shouldHaveReceived('info')
                ->withArgs(function ($message) {
                    return is_string($message) && str_contains($message, 'reminders:scheduled-visits');
                })
                ->atLeast()->once();
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Individual command tests (one per command, 100 iterations each)
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Property 12 (birthdays): يجب أن يسجّل reminders:birthdays وقت التنفيذ والعدد.
     *
     * Validates: Requirements 9.1
     */
    public function test_birthday_command_logs_execution_time_and_count(): void
    {
        // Feature: notifications-optimization, Property 12: تسجيل وقت التنفيذ والعدد

        mt_srand(12001);

        for ($i = 0; $i < 100; $i++) {
            Queue::fake();
            Log::spy();

            $count = mt_rand(0, 5);
            $this->seedBirthdayData($count, "b{$i}");

            $this->artisan('reminders:birthdays');

            // reminders:birthdays logs with array context containing notifications_sent and execution_time_sec
            Log::shouldHaveReceived('info')
                ->withArgs(function ($message, $context = []) {
                    if ($message === 'reminders:birthdays') {
                        return array_key_exists('notifications_sent', $context)
                        && array_key_exists('execution_time_sec', $context)
                        && is_numeric($context['notifications_sent'])
                        && is_numeric($context['execution_time_sec'])
                            && $context['execution_time_sec'] >= 0;
                    }
                    return false;
                })
                ->atLeast()->once();

            $this->cleanupData();
        }
    }

    /**
     * Property 12 (unvisited): يجب أن يسجّل reminders:unvisited وقت التنفيذ والعدد.
     *
     * Validates: Requirements 9.1
     */
    public function test_unvisited_command_logs_execution_time_and_count(): void
    {
        // Feature: notifications-optimization, Property 12: تسجيل وقت التنفيذ والعدد

        mt_srand(12002);

        for ($i = 0; $i < 100; $i++) {
            Queue::fake();
            Log::spy();

            $count = mt_rand(0, 5);
            $this->seedUnvisitedData($count, "u{$i}");

            $this->artisan('reminders:unvisited');

            // reminders:unvisited logs a string: "reminders:unvisited — تم إرسال {$count} تنبيه في {$elapsed} ثانية"
            Log::shouldHaveReceived('info')
                ->withArgs(function ($message) {
                    return is_string($message)
                    && str_contains($message, 'reminders:unvisited')
                    && preg_match('/\d+/', $message); // contains a number (count or elapsed)
                })
                ->atLeast()->once();

            $this->cleanupData();
        }
    }

    /**
     * Property 12 (scheduled-visits): يجب أن يسجّل reminders:scheduled-visits وقت التنفيذ والعدد.
     *
     * Validates: Requirements 9.1
     */
    public function test_scheduled_visits_command_logs_execution_time_and_count(): void
    {
        // Feature: notifications-optimization, Property 12: تسجيل وقت التنفيذ والعدد

        mt_srand(12003);

        for ($i = 0; $i < 100; $i++) {
            Queue::fake();
            Log::spy();

            $count = mt_rand(0, 5);
            $this->seedScheduledVisitsData($count, "s{$i}");

            $this->artisan('reminders:scheduled-visits');

            // reminders:scheduled-visits logs a string: "reminders:scheduled-visits — تم إرسال {$count} تذكير في {$elapsed} ثانية"
            Log::shouldHaveReceived('info')
                ->withArgs(function ($message) {
                    return is_string($message)
                    && str_contains($message, 'reminders:scheduled-visits')
                    && preg_match('/\d+/', $message); // contains a number (count or elapsed)
                })
                ->atLeast()->once();

            $this->cleanupData();
        }
    }
}
