<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * إضافة نوع 'servant_registered' إلى enum الخاص بـ type في جدول ministry_notifications
     * Requirements: 5.1, 5.2, 5.3, 5.5
     *
     * Note: SQLite doesn't support ENUM or ALTER COLUMN, so we check constraints in the application layer.
     * For MySQL/PostgreSQL, we would modify the enum type.
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            // MySQL: تعديل enum لإضافة 'servant_registered'
            DB::statement("ALTER TABLE ministry_notifications MODIFY COLUMN type ENUM(
                'birthday',
                'critical_case',
                'visit_reminder',
                'unvisited_alert',
                'new_beneficiary',
                'servant_registered'
            ) NOT NULL");
        } elseif ($driver === 'pgsql') {
            // PostgreSQL: إضافة القيمة الجديدة إلى enum type
            DB::statement("ALTER TYPE ministry_notification_type ADD VALUE IF NOT EXISTS 'servant_registered'");
        } elseif ($driver === 'sqlite') {
            // SQLite: إعادة إنشاء الجدول مع القيمة الجديدة
            // SQLite لا يدعم ALTER COLUMN، لذا نحتاج لإعادة إنشاء الجدول

            // إنشاء جدول مؤقت بالبنية الجديدة
            DB::statement('CREATE TABLE ministry_notifications_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                user_id INTEGER NOT NULL,
                type TEXT NOT NULL CHECK(type IN (
                    "birthday",
                    "critical_case",
                    "visit_reminder",
                    "unvisited_alert",
                    "new_beneficiary",
                    "servant_registered"
                )),
                title TEXT NOT NULL,
                body TEXT NOT NULL,
                data TEXT,
                read_at DATETIME,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
            )');

            // نسخ البيانات من الجدول القديم
            DB::statement('INSERT INTO ministry_notifications_new
                SELECT * FROM ministry_notifications');

            // حذف الجدول القديم
            DB::statement('DROP TABLE ministry_notifications');

            // إعادة تسمية الجدول الجديد
            DB::statement('ALTER TABLE ministry_notifications_new RENAME TO ministry_notifications');

            // إعادة إنشاء الـ indexes
            DB::statement('CREATE INDEX ministry_notifications_user_id_read_at_index
                ON ministry_notifications(user_id, read_at)');
            DB::statement('CREATE INDEX mn_user_created_idx
                ON ministry_notifications(user_id, created_at)');
            DB::statement('CREATE INDEX mn_user_unread_idx
                ON ministry_notifications(user_id, read_at) WHERE read_at IS NULL');
        }
        // التحقق من القيم يتم في application layer
    }

    /**
     * إزالة نوع 'servant_registered' من enum
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        // التحقق من عدم وجود سجلات بهذا النوع قبل الحذف
        $count = DB::table('ministry_notifications')
            ->where('type', 'servant_registered')
            ->count();

        if ($count > 0) {
            throw new \Exception("Cannot rollback: {$count} notifications with type 'servant_registered' exist.");
        }

        if ($driver === 'mysql') {
            // MySQL: إعادة enum إلى الحالة السابقة
            DB::statement("ALTER TABLE ministry_notifications MODIFY COLUMN type ENUM(
                'birthday',
                'critical_case',
                'visit_reminder',
                'unvisited_alert',
                'new_beneficiary'
            ) NOT NULL");
        } elseif ($driver === 'pgsql') {
            // PostgreSQL: لا يمكن حذف قيمة من enum type بسهولة
            // يتطلب إعادة إنشاء النوع بالكامل
            throw new \Exception("Cannot remove enum value in PostgreSQL without recreating the type.");
        }
        // SQLite: لا يدعم enum، لذا لا نحتاج لفعل شيء
    }
};
