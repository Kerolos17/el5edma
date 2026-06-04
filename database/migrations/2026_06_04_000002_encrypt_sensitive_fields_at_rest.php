<?php

declare(strict_types=1);

/**
 * Encrypt sensitive PHI/PII fields at rest.
 *
 * Widens varchar columns that will now hold encrypted payloads (~200+ chars)
 * and encrypts every existing non-null value using Laravel's Crypt facade.
 *
 * Fields covered:
 *   - beneficiaries: medical_notes, financial_notes, doctor_name, hospital_name
 *   - medications: notes
 *   - prayer_requests: body
 *
 * Idempotent for already-encrypted values (decrypt fails → skip re-encrypting).
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
        // 1. Widen varchar columns so they can hold encrypted payloads.
        Schema::table('beneficiaries', function (Blueprint $table): void {
            $table->text('doctor_name')->nullable()->change();
            $table->text('hospital_name')->nullable()->change();
        });

        // 2. Encrypt existing beneficiary rows.
        $this->encryptTable('beneficiaries', ['medical_notes', 'financial_notes', 'doctor_name', 'hospital_name']);

        // 3. Encrypt existing medication rows.
        $this->encryptTable('medications', ['notes']);

        // 4. Encrypt existing prayer_request rows.
        $this->encryptTable('prayer_requests', ['body']);
    }

    public function down(): void
    {
        // Decrypt values back to plaintext.
        $this->decryptTable('beneficiaries', ['medical_notes', 'financial_notes', 'doctor_name', 'hospital_name']);
        $this->decryptTable('medications', ['notes']);
        $this->decryptTable('prayer_requests', ['body']);

        Schema::table('beneficiaries', function (Blueprint $table): void {
            $table->string('doctor_name', 100)->nullable()->change();
            $table->string('hospital_name', 100)->nullable()->change();
        });
    }

    // ─────────────────────────────────────────────────────────────

    private function encryptTable(string $table, array $columns): void
    {
        DB::table($table)->orderBy('id')->each(function ($row) use ($table, $columns): void {
            $updates = [];

            foreach ($columns as $column) {
                $value = $row->{$column};

                if ($value === null || $value === '') {
                    continue;
                }

                try {
                    Crypt::decryptString($value);
                    // Already encrypted — skip.
                } catch (Throwable) {
                    $updates[$column] = Crypt::encryptString($value);
                }
            }

            if (! empty($updates)) {
                DB::table($table)->where('id', $row->id)->update($updates);
            }
        });
    }

    private function decryptTable(string $table, array $columns): void
    {
        DB::table($table)->orderBy('id')->each(function ($row) use ($table, $columns): void {
            $updates = [];

            foreach ($columns as $column) {
                $value = $row->{$column};

                if ($value === null || $value === '') {
                    continue;
                }

                try {
                    $updates[$column] = Crypt::decryptString($value);
                } catch (Throwable) {
                    // Already plaintext — skip.
                }
            }

            if (! empty($updates)) {
                DB::table($table)->where('id', $row->id)->update($updates);
            }
        });
    }
};
