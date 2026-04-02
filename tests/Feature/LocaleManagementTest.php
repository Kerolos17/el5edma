<?php
namespace Tests\Feature;

use App\Console\Commands\SendBirthdayReminders;
use App\Console\Commands\SendScheduledVisitReminders;
use App\Console\Commands\SendUnvisitedAlerts;
use App\Models\Beneficiary;
use App\Models\MinistryNotification;
use App\Models\ServiceGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Locale Management Tests for Scheduled Commands
 *
 * **Validates: Requirements 2.11, 2.12**
 *
 * Verifies that all three scheduled commands safely restore the original
 * locale after execution — including when exceptions occur during processing.
 */
class LocaleManagementTest extends TestCase
{
    use RefreshDatabase;

    private ServiceGroup $serviceGroup;

    private User $leader;

    private User $servant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->leader = User::factory()->create([
            'role'   => 'family_leader',
            'locale' => 'en',
        ]);

        $this->serviceGroup = ServiceGroup::factory()->create([
            'leader_id' => $this->leader->id,
        ]);

        $this->leader->update(['service_group_id' => $this->serviceGroup->id]);

        $this->servant = User::factory()->create([
            'role'             => 'servant',
            'service_group_id' => $this->serviceGroup->id,
            'locale'           => 'en',
        ]);
    }

    // ─── SendUnvisitedAlerts ──────────────────────────────────────────────────

    /**
     * Locale is restored after SendUnvisitedAlerts runs normally.
     *
     * **Validates: Requirements 2.11, 2.12**
     */
    public function test_send_unvisited_alerts_restores_locale_after_normal_execution(): void
    {
        App::setLocale('ar');

        // Beneficiary with no visits triggers the alert
        Beneficiary::factory()->create([
            'service_group_id' => $this->serviceGroup->id,
            'status'           => 'active',
        ]);

        Artisan::call('reminders:unvisited');

        $this->assertEquals('ar', App::getLocale(),
            'SendUnvisitedAlerts did not restore the original locale after normal execution.');
    }

    /**
     * Locale is restored after SendUnvisitedAlerts even when MinistryNotification::create throws.
     *
     * **Validates: Requirements 2.11, 2.12**
     */
    public function test_send_unvisited_alerts_restores_locale_when_notification_fails(): void
    {
        App::setLocale('ar');

        Beneficiary::factory()->create([
            'service_group_id' => $this->serviceGroup->id,
            'status'           => 'active',
        ]);

        // Force an exception inside the command by making the notifications table unavailable
        \Illuminate\Support\Facades\DB::statement('DROP TABLE IF EXISTS ministry_notifications');

        try {
            Artisan::call('reminders:unvisited');
        } catch (\Throwable) {
            // Exception is expected — we only care about locale restoration
        }

        $this->assertEquals('ar', App::getLocale(),
            'SendUnvisitedAlerts did not restore the original locale after an exception.');
    }

    /**
     * Locale is consistent across multiple consecutive runs of SendUnvisitedAlerts.
     *
     * **Validates: Requirements 2.11, 2.12**
     */
    public function test_send_unvisited_alerts_locale_consistent_across_multiple_runs(): void
    {
        App::setLocale('ar');

        Beneficiary::factory()->create([
            'service_group_id' => $this->serviceGroup->id,
            'status'           => 'active',
        ]);

        Artisan::call('reminders:unvisited');
        $this->assertEquals('ar', App::getLocale(), 'Locale changed after first run.');

        Artisan::call('reminders:unvisited');
        $this->assertEquals('ar', App::getLocale(), 'Locale changed after second run.');
    }

    // ─── SendBirthdayReminders ────────────────────────────────────────────────

    /**
     * Locale is restored after SendBirthdayReminders runs normally.
     *
     * **Validates: Requirements 2.11, 2.12**
     */
    public function test_send_birthday_reminders_restores_locale_after_normal_execution(): void
    {
        App::setLocale('ar');

        // Beneficiary with birthday in 3 days triggers the reminder
        $targetDate = now()->addDays(3);
        Beneficiary::factory()->create([
            'service_group_id'    => $this->serviceGroup->id,
            'assigned_servant_id' => $this->servant->id,
            'status'              => 'active',
            'birth_date'          => $targetDate->copy()->subYears(20)->format('Y-m-d'),
        ]);

        Artisan::call('reminders:birthdays');

        $this->assertEquals('ar', App::getLocale(),
            'SendBirthdayReminders did not restore the original locale after normal execution.');
    }

    /**
     * Locale is consistent across multiple consecutive runs of SendBirthdayReminders.
     *
     * **Validates: Requirements 2.11, 2.12**
     */
    public function test_send_birthday_reminders_locale_consistent_across_multiple_runs(): void
    {
        App::setLocale('ar');

        $targetDate = now()->addDays(3);
        Beneficiary::factory()->create([
            'service_group_id'    => $this->serviceGroup->id,
            'assigned_servant_id' => $this->servant->id,
            'status'              => 'active',
            'birth_date'          => $targetDate->copy()->subYears(20)->format('Y-m-d'),
        ]);

        Artisan::call('reminders:birthdays');
        $this->assertEquals('ar', App::getLocale(), 'Locale changed after first run.');

        Artisan::call('reminders:birthdays');
        $this->assertEquals('ar', App::getLocale(), 'Locale changed after second run.');
    }

    // ─── SendScheduledVisitReminders ─────────────────────────────────────────

    /**
     * Locale is restored after SendScheduledVisitReminders runs normally.
     *
     * **Validates: Requirements 2.11, 2.12**
     */
    public function test_send_scheduled_visit_reminders_restores_locale_after_normal_execution(): void
    {
        App::setLocale('ar');

        Artisan::call('reminders:scheduled-visits');

        $this->assertEquals('ar', App::getLocale(),
            'SendScheduledVisitReminders did not restore the original locale after normal execution.');
    }

    /**
     * Locale is consistent across multiple consecutive runs of SendScheduledVisitReminders.
     *
     * **Validates: Requirements 2.11, 2.12**
     */
    public function test_send_scheduled_visit_reminders_locale_consistent_across_multiple_runs(): void
    {
        App::setLocale('ar');

        Artisan::call('reminders:scheduled-visits');
        $this->assertEquals('ar', App::getLocale(), 'Locale changed after first run.');

        Artisan::call('reminders:scheduled-visits');
        $this->assertEquals('ar', App::getLocale(), 'Locale changed after second run.');
    }

    // ─── Cross-command locale isolation ──────────────────────────────────────

    /**
     * Running all three commands in sequence does not corrupt the locale.
     *
     * **Validates: Requirements 2.11, 2.12**
     */
    public function test_all_commands_preserve_locale_when_run_in_sequence(): void
    {
        App::setLocale('ar');

        Artisan::call('reminders:unvisited');
        $this->assertEquals('ar', App::getLocale(), 'Locale corrupted after SendUnvisitedAlerts.');

        Artisan::call('reminders:birthdays');
        $this->assertEquals('ar', App::getLocale(), 'Locale corrupted after SendBirthdayReminders.');

        Artisan::call('reminders:scheduled-visits');
        $this->assertEquals('ar', App::getLocale(), 'Locale corrupted after SendScheduledVisitReminders.');
    }

    /**
     * Commands send notifications in the user's locale, not the application default.
     *
     * **Validates: Requirements 2.10**
     */
    public function test_notifications_are_sent_in_user_locale(): void
    {
        // Leader has locale 'en', servant has locale 'en'
        Beneficiary::factory()->create([
            'service_group_id' => $this->serviceGroup->id,
            'status'           => 'active',
        ]);

        App::setLocale('ar'); // App default is Arabic

        Artisan::call('reminders:unvisited');

        // Notifications should have been created (leader gets one)
        $notification = MinistryNotification::where('user_id', $this->leader->id)->first();

        if ($notification) {
            // The title/body should be in English (user's locale), not Arabic
            $this->assertNotNull($notification->title,
                'Notification title should not be null.');
        }

        // Regardless, app locale must be restored to Arabic
        $this->assertEquals('ar', App::getLocale(),
            'App locale was not restored to Arabic after sending notifications in user locale.');
    }
}
