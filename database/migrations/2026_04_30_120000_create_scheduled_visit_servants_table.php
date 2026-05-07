<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_visit_servants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheduled_visit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('servant_id')->constrained('users')->cascadeOnDelete();
            $table->unique(['scheduled_visit_id', 'servant_id'], 'scheduled_visit_servants_unique');
            $table->index('servant_id', 'scheduled_visit_servants_servant_idx');
        });

        $rows = DB::table('scheduled_visits')
            ->whereNotNull('assigned_servant_id')
            ->select('id as scheduled_visit_id', 'assigned_servant_id as servant_id')
            ->get()
            ->map(fn (object $row) => [
                'scheduled_visit_id' => $row->scheduled_visit_id,
                'servant_id' => $row->servant_id,
            ])
            ->all();

        if ($rows !== []) {
            DB::table('scheduled_visit_servants')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_visit_servants');
    }
};
