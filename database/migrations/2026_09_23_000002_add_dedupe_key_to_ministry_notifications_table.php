<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Idempotency key for scheduled reminders (e.g. birthdays): reruns on
     * the same day return the existing row instead of duplicating it.
     */
    public function up(): void
    {
        Schema::table('ministry_notifications', function (Blueprint $table) {
            $table->string('dedupe_key', 128)->nullable()->unique()->after('data');
        });
    }

    public function down(): void
    {
        Schema::table('ministry_notifications', function (Blueprint $table) {
            $table->dropUnique(['dedupe_key']);
            $table->dropColumn('dedupe_key');
        });
    }
};
