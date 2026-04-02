<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beneficiary_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_servant_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->date('scheduled_date');
            $table->time('scheduled_time');
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->timestamp('reminder_sent_at')->nullable();

            // ربط الزيارة المجدولة بالزيارة الفعلية بعد التنفيذ
            $table->foreignId('completed_visit_id')
                ->nullable()
                ->constrained('visits')
                ->nullOnDelete();

            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_visits');
    }
};
