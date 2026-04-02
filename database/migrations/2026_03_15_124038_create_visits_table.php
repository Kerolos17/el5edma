<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beneficiary_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->enum('type', ['home_visit', 'phone_call', 'church_meeting']);
            $table->dateTime('visit_date');
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->enum('beneficiary_status', [
                'great', 'good', 'needs_follow', 'critical',
            ]);
            $table->text('feedback')->nullable();
            $table->boolean('is_critical')->default(false);
            $table->timestamp('critical_resolved_at')->nullable();
            $table->foreignId('critical_resolved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->boolean('needs_family_leader')->default(false);
            $table->boolean('needs_service_leader')->default(false);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
