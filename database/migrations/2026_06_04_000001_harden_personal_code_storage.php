<?php

/**
 * Security hardening migration for personal codes.
 *
 * 1. Widens `personal_code` so it can hold an encrypted payload (~200 chars).
 * 2. Encrypts every existing plaintext code at rest.
 * 3. Recomputes `personal_code_hash` as a peppered HMAC (keyed by APP_KEY)
 *    instead of an unsalted SHA-256.
 *
 * Idempotent: rows already encrypted are detected via decrypt() and left
 * as-is for the value, while the hash is always recomputed deterministically.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->text('personal_code')->nullable()->change();
        });

        $pepper = (string) config('app.key');

        DB::table('users')->whereNotNull('personal_code')->orderBy('id')->each(function ($row) use ($pepper): void {
            $stored = $row->personal_code;

            try {
                $plain = Crypt::decryptString($stored);
            } catch (Throwable) {
                $plain = $stored; // still plaintext
            }

            DB::table('users')->where('id', $row->id)->update([
                'personal_code'      => Crypt::encryptString($plain),
                'personal_code_hash' => hash_hmac('sha256', $plain, $pepper),
            ]);
        });
    }

    public function down(): void
    {
        // Decrypt values back to plaintext so older code paths keep working.
        DB::table('users')->whereNotNull('personal_code')->orderBy('id')->each(function ($row): void {
            try {
                $plain = Crypt::decryptString($row->personal_code);
            } catch (Throwable) {
                $plain = $row->personal_code;
            }

            DB::table('users')->where('id', $row->id)->update([
                'personal_code'      => $plain,
                'personal_code_hash' => hash('sha256', $plain),
            ]);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('personal_code', 10)->nullable()->change();
        });
    }
};
