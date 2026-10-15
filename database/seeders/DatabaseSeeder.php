<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use PreventsProductionDemoSeeding;

    public function run(): void
    {
        $this->preventProductionDemoSeeding();

        $this->call([
            RolesAndPermissionsSeeder::class,
            AdminUserSeeder::class,
            ServiceGroupSeeder::class,
            BeneficiarySeeder::class,
            MinistryActivitySeeder::class,
        ]);
    }
}
