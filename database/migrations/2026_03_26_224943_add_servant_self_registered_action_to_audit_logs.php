<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // SQLite يتطلب إعادة إنشاء الجدول لتعديل CHECK constraint
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropColumn('action');
            });

            Schema::table('audit_logs', function (Blueprint $table) {
                $table->enum('action', ['created', 'updated', 'deleted', 'servant_self_registered'])->after('model_id');
            });
        } elseif ($driver === 'mysql') {
            // إضافة 'servant_self_registered' إلى enum الخاص بـ action column
            DB::statement("ALTER TABLE audit_logs MODIFY COLUMN action ENUM('created', 'updated', 'deleted', 'servant_self_registered')");
        } elseif ($driver === 'pgsql') {
            // PostgreSQL يتطلب إضافة القيمة إلى النوع المخصص
            DB::statement("ALTER TYPE audit_log_action ADD VALUE IF NOT EXISTS 'servant_self_registered'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // إرجاع الـ enum إلى القيم الأصلية
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropColumn('action');
            });

            Schema::table('audit_logs', function (Blueprint $table) {
                $table->enum('action', ['created', 'updated', 'deleted'])->after('model_id');
            });
        } elseif ($driver === 'mysql') {
            // إرجاع enum إلى القيم الأصلية
            DB::statement("ALTER TABLE audit_logs MODIFY COLUMN action ENUM('created', 'updated', 'deleted')");
        } elseif ($driver === 'pgsql') {
            // PostgreSQL لا يدعم حذف قيمة من enum type
            // يجب إعادة إنشاء النوع بالكامل إذا لزم الأمر
        }
    }
};
