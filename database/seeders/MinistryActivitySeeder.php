<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Beneficiary;
use App\Models\MinistryNotification;
use App\Models\ScheduledVisit;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo activity data: scheduled visits + sample notifications.
 *
 * Skips itself when scheduled visits already exist, so re-running
 * `db:seed` never duplicates demo rows. Push devices are intentionally
 * NOT seeded (fake FCM tokens would generate failed sends).
 */
class MinistryActivitySeeder extends Seeder
{
    public function run(): void
    {
        if (ScheduledVisit::query()->exists()) {
            $this->command->info('⏭️  Scheduled visits already seeded — skipping.');

            return;
        }

        $servants = User::query()
            ->where('role', 'servant')
            ->where('is_active', true)
            ->whereNotNull('service_group_id')
            ->orderBy('id')
            ->limit(4)
            ->get();

        if ($servants->isEmpty()) {
            $this->command->warn('No servants with groups found — skipping activity seed.');

            return;
        }

        $created = 0;

        foreach ($servants as $index => $servant) {
            $beneficiary = Beneficiary::query()
                ->where('service_group_id', $servant->service_group_id)
                ->where('status', 'active')
                ->first();

            if (! $beneficiary) {
                continue;
            }

            // Upcoming visit (this week).
            $upcoming = ScheduledVisit::create([
                'beneficiary_id'      => $beneficiary->id,
                'assigned_servant_id' => $servant->id,
                'scheduled_date'      => now()->addDays(1 + $index)->toDateString(),
                'scheduled_time'      => '18:00',
                'notes'               => 'زيارة متابعة دورية',
                'status'              => 'pending',
                'created_by'          => $servant->id,
            ]);
            $upcoming->syncAssignedServants([$servant->id]);
            $created++;

            // Past completed visit from last week.
            $past = ScheduledVisit::create([
                'beneficiary_id'      => $beneficiary->id,
                'assigned_servant_id' => $servant->id,
                'scheduled_date'      => now()->subDays(3)->toDateString(),
                'scheduled_time'      => '17:00',
                'notes'               => null,
                'status'              => 'completed',
                'created_by'          => $servant->id,
            ]);
            $past->syncAssignedServants([$servant->id]);
            $created++;
        }

        // Sample inbox content so the bell/dropdowns are demonstrable.
        $demoServant = User::query()->where('email', 'servant@ministry.local')->first();

        if ($demoServant) {
            MinistryNotification::firstOrCreate(
                ['dedupe_key' => 'demo:servant@ministry.local:1'],
                [
                    'user_id' => $demoServant->id,
                    'type'    => 'visit_reminder',
                    'title'   => 'تذكير بزيارة',
                    'body'    => 'لديك زيارة مجدولة غداً — تحقق من قائمة الزيارات المجدولة.',
                    'data'    => json_encode(['url' => '/servant/scheduled-visits', 'locale' => 'ar']),
                ],
            );
        }

        $this->command->info("✅ Activity seeded: {$created} scheduled visits + demo notifications.");
    }
}
