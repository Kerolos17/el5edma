<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Many-to-many: الخدام المشاركون في كل زيارة
        Schema::create('visit_servants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('servant_id')->constrained('users')->cascadeOnDelete();
            $table->unique(['visit_id', 'servant_id']); // منع التكرار
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_servants');
    }
};
