<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_groups', function (Blueprint $table) {
            // إضافة حقل registration_token (string, 64 chars, unique, nullable)
            $table->string('registration_token', 64)->unique()->nullable()->after('is_active');

            // إضافة حقل registration_token_generated_at (timestamp, nullable)
            $table->timestamp('registration_token_generated_at')->nullable()->after('registration_token');

            // إضافة index على registration_token للبحث السريع
            $table->index('registration_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_groups', function (Blueprint $table) {
            // حذف الـ unique constraint أولاً
            $table->dropUnique(['registration_token']);

            // حذف الـ index
            $table->dropIndex(['registration_token']);

            // حذف الحقول
            $table->dropColumn(['registration_token', 'registration_token_generated_at']);
        });
    }
};
