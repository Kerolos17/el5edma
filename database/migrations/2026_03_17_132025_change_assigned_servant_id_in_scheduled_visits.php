<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scheduled_visits', function (Blueprint $table) {
            // Drop the existing cascadeOnDelete foreign key
            $table->dropForeign(['assigned_servant_id']);

            // Make the column nullable and re-add with nullOnDelete
            $table->foreignId('assigned_servant_id')
                  ->nullable()
                  ->change();

            $table->foreign('assigned_servant_id')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('scheduled_visits', function (Blueprint $table) {
            $table->dropForeign(['assigned_servant_id']);

            $table->foreignId('assigned_servant_id')
                  ->nullable(false)
                  ->change();

            $table->foreign('assigned_servant_id')
                  ->references('id')
                  ->on('users')
                  ->cascadeOnDelete();
        });
    }
};
