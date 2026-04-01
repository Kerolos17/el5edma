<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'personal_code_hash')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('personal_code_hash', 64)->nullable()->unique()->after('personal_code');
            });
        }

        // Populate hash for existing codes in a single query
        DB::statement('UPDATE users SET personal_code_hash = SHA2(personal_code, 256) WHERE personal_code IS NOT NULL AND personal_code_hash IS NULL');
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('personal_code_hash');
        });
    }
};
