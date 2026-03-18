<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Beneficiaries indexes
        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->index('service_group_id', 'idx_beneficiaries_service_group_id');
            $table->index('assigned_servant_id', 'idx_beneficiaries_assigned_servant_id');
            $table->index('status', 'idx_beneficiaries_status');
            $table->index('governorate', 'idx_beneficiaries_governorate');
        });

        // Visits indexes
        Schema::table('visits', function (Blueprint $table) {
            $table->index('beneficiary_id', 'idx_visits_beneficiary_id');
            $table->index('visit_date', 'idx_visits_visit_date');
            $table->index('created_by', 'idx_visits_created_by');
            $table->index(['is_critical', 'critical_resolved_at'], 'idx_visits_critical_cases');
        });

        // Scheduled visits indexes
        Schema::table('scheduled_visits', function (Blueprint $table) {
            $table->index('beneficiary_id', 'idx_scheduled_visits_beneficiary_id');
            $table->index('assigned_servant_id', 'idx_scheduled_visits_assigned_servant_id');
            $table->index('scheduled_date', 'idx_scheduled_visits_scheduled_date');
        });

        // Service groups indexes
        Schema::table('service_groups', function (Blueprint $table) {
            $table->index('leader_id', 'idx_service_groups_leader_id');
            $table->index('service_leader_id', 'idx_service_groups_service_leader_id');
            $table->index('is_active', 'idx_service_groups_is_active');
        });

        // Users indexes
        Schema::table('users', function (Blueprint $table) {
            $table->index('service_group_id', 'idx_users_service_group_id');
            $table->index('role', 'idx_users_role');
            $table->index('is_active', 'idx_users_is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->dropIndex('idx_beneficiaries_service_group_id');
            $table->dropIndex('idx_beneficiaries_assigned_servant_id');
            $table->dropIndex('idx_beneficiaries_status');
            $table->dropIndex('idx_beneficiaries_governorate');
        });

        Schema::table('visits', function (Blueprint $table) {
            $table->dropIndex('idx_visits_beneficiary_id');
            $table->dropIndex('idx_visits_visit_date');
            $table->dropIndex('idx_visits_created_by');
            $table->dropIndex('idx_visits_critical_cases');
        });

        Schema::table('scheduled_visits', function (Blueprint $table) {
            $table->dropIndex('idx_scheduled_visits_beneficiary_id');
            $table->dropIndex('idx_scheduled_visits_assigned_servant_id');
            $table->dropIndex('idx_scheduled_visits_scheduled_date');
        });

        Schema::table('service_groups', function (Blueprint $table) {
            $table->dropIndex('idx_service_groups_leader_id');
            $table->dropIndex('idx_service_groups_service_leader_id');
            $table->dropIndex('idx_service_groups_is_active');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_service_group_id');
            $table->dropIndex('idx_users_role');
            $table->dropIndex('idx_users_is_active');
        });
    }
};
