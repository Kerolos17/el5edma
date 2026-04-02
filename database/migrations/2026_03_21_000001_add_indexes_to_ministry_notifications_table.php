<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ministry_notifications', function (Blueprint $table) {
            // للاستعلامات المرتبة بـ created_at لمستخدم معين
            $table->index(['user_id', 'created_at'], 'mn_user_created_idx');

            // لاستعلام عدد غير المقروء (يشمل read_at و created_at)
            $table->index(['user_id', 'read_at', 'created_at'], 'mn_user_unread_idx');
        });
    }

    public function down(): void
    {
        Schema::table('ministry_notifications', function (Blueprint $table) {
            $table->dropIndex('mn_user_created_idx');
            $table->dropIndex('mn_user_unread_idx');
        });
    }
};
