<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The MySQL server on the hosting runs in UTC, and these immutable tables
 * filled created_at via CURRENT_TIMESTAMP — so every DB-stamped row was
 * stored 3 hours behind the app timezone (Africa/Cairo) and displayed with
 * a +3h "time ago" offset. Rows written explicitly from PHP (now()) are
 * already app-timezone and must not shift.
 *
 * Measured on the live DB (2026-09-26): audit_logs and ministry_notifications
 * type=critical_case were DB-stamped (UTC); servant_registered /
 * new_beneficiary came from InternalNotificationService (PHP, app tz).
 * Models now stamp from PHP, so this shift is a one-time correction.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("UPDATE audit_logs SET created_at = datetime(created_at, '+3 hours')");
            DB::statement("UPDATE ministry_notifications SET created_at = datetime(created_at, '+3 hours') WHERE type NOT IN ('servant_registered', 'new_beneficiary')");

            return;
        }

        DB::statement('UPDATE audit_logs SET created_at = DATE_ADD(created_at, INTERVAL 3 HOUR)');
        DB::statement('UPDATE ministry_notifications SET created_at = DATE_ADD(created_at, INTERVAL 3 HOUR) WHERE type NOT IN (\'servant_registered\', \'new_beneficiary\')');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("UPDATE audit_logs SET created_at = datetime(created_at, '-3 hours')");
            DB::statement("UPDATE ministry_notifications SET created_at = datetime(created_at, '-3 hours') WHERE type NOT IN ('servant_registered', 'new_beneficiary')");

            return;
        }

        DB::statement('UPDATE audit_logs SET created_at = DATE_SUB(created_at, INTERVAL 3 HOUR)');
        DB::statement('UPDATE ministry_notifications SET created_at = DATE_SUB(created_at, INTERVAL 3 HOUR) WHERE type NOT IN (\'servant_registered\', \'new_beneficiary\')');
    }
};
