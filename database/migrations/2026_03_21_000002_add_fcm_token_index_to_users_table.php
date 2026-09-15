<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Intentionally left blank.
        //
        // users.fcm_token is a legacy TEXT column kept temporarily for
        // backwards compatibility. Indexing the full TEXT value is invalid on
        // MySQL without a prefix length and is no longer needed because active
        // device lookups use push_devices.token_hash instead.
    }

    public function down(): void
    {
        // No schema change to roll back.
    }
};
