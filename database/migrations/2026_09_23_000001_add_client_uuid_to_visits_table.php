<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Idempotency key for offline-synced visits: the client generates one
     * UUID per queued record, so replays/retries never create duplicates.
     */
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->string('client_uuid', 64)->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropUnique(['client_uuid']);
            $table->dropColumn('client_uuid');
        });
    }
};
