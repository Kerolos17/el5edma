<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Crypt;

return new class extends Migration
{
    public function up(): void
    {
        // Decrypt any encrypted personal_code values back to plain text
        DB::table('users')->whereNotNull('personal_code')->eachById(function ($user) {
            try {
                $decrypted = Crypt::decryptString($user->personal_code);
                DB::table('users')->where('id', $user->id)->update([
                    'personal_code' => $decrypted,
                ]);
            } catch (\Throwable) {
                // Already plain text, skip
            }
        });

        // Revert column back to varchar(10)
        Schema::table('users', function (Blueprint $table) {
            $table->string('personal_code', 10)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('personal_code')->nullable()->change();
        });
    }
};
