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

        // Populate hash for existing codes
        DB::table('users')->whereNotNull('personal_code')->eachById(function ($user) {
            DB::table('users')->where('id', $user->id)->update([
                'personal_code_hash' => hash('sha256', $user->personal_code),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('personal_code_hash');
        });
    }
};
