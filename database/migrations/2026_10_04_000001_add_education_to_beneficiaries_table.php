<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beneficiaries', function (Blueprint $table) {
            // none = خارج التعليم، center = مركز، school = مدرسة.
            $table->enum('education_status', ['none', 'center', 'school'])->default('none')->after('disability_degree');
            // اسم المدرسة أو المركز حسب الحالة.
            $table->string('education_place_name')->nullable()->after('education_status');
            // الصف الدراسي (نص حر: "الصف الرابع الابتدائي" ...) — للمدرسة فقط.
            $table->string('education_grade')->nullable()->after('education_place_name');
        });
    }

    public function down(): void
    {
        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->dropColumn(['education_status', 'education_place_name', 'education_grade']);
        });
    }
};
