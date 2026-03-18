<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ⚠️ هذا الجدول immutable — لا يوجد updated_at عمداً
        Schema::create('medical_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beneficiary_id')->constrained()->cascadeOnDelete();
            // الملفات محفوظة في storage/app/private/medical/{beneficiary_id}/
            $table->string('file_path');
            $table->enum('file_type', ['report', 'image', 'document']);
            $table->string('title');
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamp('created_at')->useCurrent();
            // NO updated_at — immutable record
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_files');
    }
};
