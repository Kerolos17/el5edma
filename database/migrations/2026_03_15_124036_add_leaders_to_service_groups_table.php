<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // migration منفصل لحل circular FK بين service_groups و users
    public function up(): void
    {
        Schema::table('service_groups', function (Blueprint $table) {
            $table->foreignId('leader_id')
                ->nullable()
                ->after('name')
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('service_leader_id')
                ->nullable()
                ->after('leader_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('service_groups', function (Blueprint $table) {
            $table->dropForeign(['leader_id']);
            $table->dropForeign(['service_leader_id']);
            $table->dropColumn(['leader_id', 'service_leader_id']);
        });
    }
};
