<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // جدول مخصص للـ in-app notifications — منفصل عن Laravel notifications
    public function up(): void
    {
        Schema::create('ministry_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->enum('type', [
                'birthday',
                'critical_case',
                'visit_reminder',
                'unvisited_alert',
                'new_beneficiary',
            ]);
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ministry_notifications');
    }
};
