<?php

/**
 * One-shot remediation migration.
 *
 * Context: an `encrypted` cast was briefly added to User::$casts for `personal_code`,
 * causing the column to store Laravel-encrypted ciphertext (~200 chars) that overflowed
 * the original varchar(10). The cast was removed, but this migration handles any
 * environments where the encrypted cast ran and data was stored encrypted.
 *
 * It attempts to decrypt existing values, then shrinks the column back to varchar(10).
 * Safe to run on already-clean databases (plain-text values pass through the try/catch).
 *
 * NOTE: down() reverts to text; migration _000002 down() also reverts to varchar(10).
 * Run rollbacks in strict reverse order to avoid conflicts.
 */
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
