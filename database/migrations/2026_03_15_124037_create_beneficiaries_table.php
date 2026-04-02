<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->id();

            // ── البيانات الأساسية ──
            $table->string('full_name');
            $table->text('photo')->nullable();
            $table->date('birth_date');
            $table->enum('gender', ['male', 'female']);
            $table->string('code', 20)->unique(); // SN-0042 — auto-generated

            // ── تواصل المخدوم ──
            $table->string('phone', 20)->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();

            // ── ولي الأمر — العائلة البيولوجية فقط — لا علاقة بـ service_group ──
            $table->string('guardian_name')->nullable();
            $table->string('guardian_phone', 20)->nullable();
            $table->string('guardian_relation', 50)->nullable();

            // ── حالة الوالدين ──
            $table->enum('father_status', ['alive', 'deceased', 'unknown'])->nullable();
            $table->date('father_death_date')->nullable();
            $table->enum('mother_status', ['alive', 'deceased', 'unknown'])->nullable();
            $table->date('mother_death_date')->nullable();

            // ── الأشقاء ──
            $table->tinyInteger('siblings_count')->unsigned()->nullable();
            $table->string('siblings_note', 255)->nullable();

            // ── الوضع المادي ──
            $table->enum('financial_status', [
                'good', 'moderate', 'poor', 'very_poor',
            ])->nullable();
            $table->text('financial_notes')->nullable();

            // ── العنوان ──
            $table->text('address_text')->nullable();
            $table->string('google_maps_url')->nullable();
            $table->string('area', 100)->nullable();
            $table->string('governorate', 100)->nullable();

            // ── التعيين الخدمي ──
            $table->foreignId('service_group_id')
                ->constrained('service_groups')
                ->cascadeOnDelete();
            $table->foreignId('assigned_servant_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->enum('status', [
                'active', 'inactive', 'moved', 'deceased',
            ])->default('active');

            // ── الحالة الطبية ──
            $table->string('disability_type', 100)->nullable();
            $table->enum('disability_degree', ['mild', 'moderate', 'severe'])->nullable();
            $table->text('health_status')->nullable();
            $table->string('doctor_name', 100)->nullable();
            $table->string('hospital_name', 100)->nullable();
            $table->date('last_medical_update')->nullable();
            $table->text('medical_notes')->nullable();

            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiaries');
    }
};
