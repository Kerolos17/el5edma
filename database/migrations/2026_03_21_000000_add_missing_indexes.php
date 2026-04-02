<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // فهرس critical_resolved_by للاستعلامات عن من أغلق الحالات الحرجة
        Schema::table('visits', function (Blueprint $table) {
            $table->index('critical_resolved_by', 'idx_visits_critical_resolved_by');
            $table->index(['beneficiary_id', 'visit_date'], 'idx_visits_beneficiary_date');
        });

        // فهارس منفصلة على visit_servants لتسريع الاستعلامات الفردية
        // (الفهرس المركب الفريد موجود بالفعل من migration الإنشاء)
        Schema::table('visit_servants', function (Blueprint $table) {
            $table->index('visit_id', 'idx_visit_servants_visit_id');
            $table->index('servant_id', 'idx_visit_servants_servant_id');
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropIndex('idx_visits_critical_resolved_by');
            $table->dropIndex('idx_visits_beneficiary_date');
        });

        Schema::table('visit_servants', function (Blueprint $table) {
            $table->dropIndex('idx_visit_servants_visit_id');
            $table->dropIndex('idx_visit_servants_servant_id');
        });
    }
};
