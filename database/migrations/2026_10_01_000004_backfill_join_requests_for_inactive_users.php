<?php

use App\Models\JoinRequest;
use Illuminate\Database\Migrations\Migration;

/**
 * Turns the historical "pending approval" queue (inactive servant accounts)
 * into reviewable join requests so they surface on the new review page.
 * Existing active accounts are left untouched — they were never requests.
 */
return new class extends Migration
{
    public function up(): void
    {
        JoinRequest::backfillInactiveUsers();
    }

    public function down(): void
    {
        // Rows created by the backfill carry no distinguishing marker beyond
        // being requests for accounts that were inactive at backfill time;
        // leaving them in place is the safe (non-destructive) reversal.
    }
};
