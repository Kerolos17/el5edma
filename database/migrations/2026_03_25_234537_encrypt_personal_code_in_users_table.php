<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // توسيع العمود لاستيعاب القيمة المشفرة (AES-256-CBC + base64 ≈ 200 حرف)
            // نتحقق أولاً لأن بعض البيئات قد تكون عدّلت الـ schema مسبقاً
            if (Schema::getColumnType('users', 'personal_code') !== 'text') {
                $table->text('personal_code')->nullable()->change();
            }

            // عمود الـ hash للبحث السريع (blind index)
            if (! Schema::hasColumn('users', 'personal_code_hash')) {
                $table->string('personal_code_hash', 64)->nullable()->unique()->after('personal_code');
            }
        });

        // تشفير الأكواد الموجودة وتوليد الـ hash
        DB::table('users')->whereNotNull('personal_code')->orderBy('id')->each(function ($user) {
            $raw = $user->personal_code;

            // تجاهل القيم المشفرة بالفعل
            if ($this->isAlreadyEncrypted($raw)) {
                // نتأكد فقط من وجود الـ hash
                if (empty($user->personal_code_hash)) {
                    try {
                        $decrypted = decrypt($raw);
                        DB::table('users')->where('id', $user->id)->update([
                            'personal_code_hash' => hash('sha256', $decrypted),
                        ]);
                    } catch (Exception) {
                        // تجاهل القيم التي لا يمكن فك تشفيرها
                    }
                }

                return;
            }

            DB::table('users')->where('id', $user->id)->update([
                'personal_code'      => encrypt($raw),
                'personal_code_hash' => hash('sha256', $raw),
            ]);
        });
    }

    public function down(): void
    {
        // فك تشفير الأكواد عند الـ rollback
        DB::table('users')->whereNotNull('personal_code')->orderBy('id')->each(function ($user) {
            try {
                $decrypted = decrypt($user->personal_code);
                DB::table('users')->where('id', $user->id)->update([
                    'personal_code' => $decrypted,
                ]);
            } catch (Exception) {
                // تجاهل القيم غير المشفرة
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'personal_code_hash')) {
                $table->dropColumn('personal_code_hash');
            }
            $table->string('personal_code', 10)->nullable()->change();
        });
    }

    private function isAlreadyEncrypted(string $value): bool
    {
        try {
            decrypt($value);

            return true;
        } catch (Exception) {
            return false;
        }
    }
};
