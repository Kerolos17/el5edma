<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Beneficiary;
use App\Models\MinistryNotification;
use App\Models\ScheduledVisit;
use App\Models\ServiceGroup;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\MinistryActivitySeeder;
use Database\Seeders\ServiceGroupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class NotificationDedupAndSeedTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    #[Test]
    public function birthday_command_rerun_same_day_creates_no_duplicates(): void
    {
        Queue::fake();

        $group   = ServiceGroup::factory()->create();
        $servant = $this->createServant($group);

        Beneficiary::factory()->create([
            'assigned_servant_id' => $servant->id,
            'service_group_id'    => $group->id,
            'birth_date'          => now()->addDays(3)->toDateString(),
        ]);

        $this->artisan('reminders:birthdays')->assertOk();
        $firstCount = MinistryNotification::where('type', 'birthday')->count();
        $this->assertGreaterThan(0, $firstCount);

        $this->artisan('reminders:birthdays')->assertOk();

        $this->assertSame(
            $firstCount,
            MinistryNotification::where('type', 'birthday')->count(),
            'Rerunning birthday reminders on the same day must not duplicate rows.',
        );
    }

    #[Test]
    public function activity_seeder_is_idempotent_and_assigns_servants(): void
    {
        // ServiceGroupSeeder upserts users without names, so it requires the
        // base accounts first — same order as DatabaseSeeder.
        $this->seed(AdminUserSeeder::class);
        $this->seed(ServiceGroupSeeder::class);

        $servant = User::where('email', 'servant@ministry.local')->firstOrFail();

        Beneficiary::factory()->create([
            'assigned_servant_id' => $servant->id,
            'service_group_id'    => $servant->service_group_id,
        ]);

        $this->seed(MinistryActivitySeeder::class);
        $firstCount = ScheduledVisit::count();
        $this->assertGreaterThan(0, $firstCount);

        $this->seed(MinistryActivitySeeder::class);

        $this->assertSame($firstCount, ScheduledVisit::count(), 'Seeder rerun must not duplicate rows.');
        $this->assertTrue(
            ScheduledVisit::where('status', 'pending')->exists()
            && ScheduledVisit::where('status', 'completed')->exists(),
        );
    }

    #[Test]
    public function cleanup_logs_deleted_count(): void
    {
        Log::spy();

        $user = $this->createSuperAdmin();

        MinistryNotification::factory()->create([
            'user_id' => $user->id,
            'read_at' => now()->subDays(100),
        ]);

        $this->artisan('notifications:cleanup')->assertOk();

        $this->assertSame(0, MinistryNotification::count());
        Log::shouldHaveReceived('info')->once();
    }
}
