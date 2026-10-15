<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\AdminUserSeeder;
use Database\Seeders\BeneficiarySeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\MinistryActivitySeeder;
use Database\Seeders\ServiceGroupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class ProductionSeederSafetyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function production_refuses_default_and_demo_seeders_before_writes(): void
    {
        $this->app['env'] = 'production';

        foreach ([
            DatabaseSeeder::class,
            AdminUserSeeder::class,
            ServiceGroupSeeder::class,
            BeneficiarySeeder::class,
            MinistryActivitySeeder::class,
        ] as $seederClass) {
            try {
                app($seederClass)->run();
                $this->fail("{$seederClass} should refuse production execution.");
            } catch (RuntimeException $exception) {
                $this->assertSame('Demo data seeders are disabled in production.', $exception->getMessage());
            }
        }

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('service_groups', 0);
        $this->assertDatabaseCount('beneficiaries', 0);
        $this->assertDatabaseCount('scheduled_visits', 0);
    }
}
