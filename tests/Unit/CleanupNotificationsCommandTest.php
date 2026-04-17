<?php

// Feature: notifications-optimization — Unit tests for notifications:cleanup command (Requirements 6.3, 6.4)

namespace Tests\Unit;

use App\Models\MinistryNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schedule;
use Tests\TestCase;

/**
 * Unit tests for notifications:cleanup command
 *
 * Requirements: 6.3, 6.4
 */
class CleanupNotificationsCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1: Running the command on an empty/clean table logs "no records" message.
     *
     * Validates: Requirements 6.4 — IF عدد السجلات المحذوفة صفراً،
     * THEN يُسجَّل رسالة معلوماتية في السجلات دون اعتبار ذلك خطأ.
     */
    public function test_command_on_empty_table_runs_successfully(): void
    {
        // Ensure the table is empty (no old read notifications)
        MinistryNotification::query()->delete();

        $this->artisan('notifications:cleanup')
            ->expectsOutputToContain('تم حذف 0 إشعار قديم')
            ->assertSuccessful();
    }

    /**
     * Test 2: The command is scheduled weekly in routes/console.php.
     *
     * Validates: Requirements 6.3 — THE Notification_System SHALL جدولة أمر
     * notifications:cleanup للتنفيذ أسبوعياً.
     */
    public function test_cleanup_command_is_scheduled_weekly(): void
    {
        // Load the console routes to register scheduled commands
        require_once base_path('routes/console.php');

        $scheduledEvents = Schedule::events();

        $cleanupEvent = collect($scheduledEvents)->first(fn ($event) => str_contains($event->command ?? '', 'notifications:cleanup'));

        $this->assertNotNull(
            $cleanupEvent,
            'notifications:cleanup is not registered in the scheduler',
        );

        // Scheduled every Friday at midnight: "0 0 * * 5"
        $this->assertEquals(
            '0 0 * * 5',
            $cleanupEvent->expression,
            'notifications:cleanup is not scheduled weekly on Friday (expected cron: 0 0 * * 5)',
        );
    }
}
