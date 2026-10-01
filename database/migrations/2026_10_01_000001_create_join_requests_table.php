<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('join_requests', function (Blueprint $table) {
            $table->id();
            // One request per account; the account is created together with
            // the request and stays gated until the request is approved.
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('service_group_id')->nullable()->constrained('service_groups')->nullOnDelete();
            // Requested role is INFORMATION only — actual privileges come from
            // users.role at approval time, never from this column.
            $table->string('desired_role', 20)->default('servant');
            $table->enum('status', ['incomplete', 'pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('final_role', 20)->nullable();
            $table->foreignId('final_service_group_id')->nullable()->constrained('service_groups')->nullOnDelete();
            $table->text('decision_note')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('service_group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('join_requests');
    }
};
