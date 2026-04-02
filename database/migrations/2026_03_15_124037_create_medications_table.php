<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beneficiary_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('dosage', 100);
            $table->tinyInteger('frequency')->unsigned(); // عدد المرات يومياً
            $table->enum('timing', ['morning', 'evening', 'with_food', 'as_needed']);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medications');
    }
};
