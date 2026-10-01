<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('join_request_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('join_request_id')->constrained('join_requests')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            // approved | rejected | changes_requested | suspended | reactivated
            $table->string('action', 30);
            $table->text('note')->nullable();
            // Stamped from PHP in the model's creating hook — the hosting DB
            // runs UTC while the app is Africa/Cairo (see the 2026_09_27 fix).
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->index(['join_request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('join_request_reviews');
    }
};
