<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // Convert ENUM to VARCHAR — removes constraint, allows any action value
            DB::statement('ALTER TABLE audit_logs MODIFY COLUMN action VARCHAR(50) NOT NULL');
        } elseif ($driver === 'sqlite') {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->string('action', 50)->change();
            });
        }
        // pgsql: already flexible, no change needed
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE audit_logs MODIFY COLUMN action ENUM('created', 'updated', 'deleted', 'servant_self_registered') NOT NULL");
        }
    }
};
