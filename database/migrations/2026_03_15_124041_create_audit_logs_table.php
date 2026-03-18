<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ⚠️ هذا الجدول immutable — لا يوجد updated_at عمداً
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('model_type'); // e.g. App\Models\Beneficiary
            $table->unsignedBigInteger('model_id');
            $table->enum('action', ['created', 'updated', 'deleted']);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            // NO updated_at — immutable record

            $table->index(['model_type', 'model_id']); // لتسريع البحث
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
