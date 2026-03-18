<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // super_admin
        $admin = User::updateOrCreate(
            ['email' => 'admin@ministry.local'],
            [
                'name'          => 'مدير النظام',
                'password'      => Hash::make('Admin@1234'),
                'personal_code' => '1111',
                'role'          => 'super_admin',
                'locale'        => 'ar',
                'is_active'     => true,
            ]
        );
        $admin->syncRoles('super_admin');

        // service_leader
        $leader = User::updateOrCreate(
            ['email' => 'leader@ministry.local'],
            [
                'name'          => 'أمين الخدمة',
                'password'      => Hash::make('Leader@1234'),
                'personal_code' => '2222',
                'role'          => 'service_leader',
                'locale'        => 'ar',
                'is_active'     => true,
            ]
        );
        $leader->syncRoles('service_leader');

        // family_leader
        $familyLeader = User::updateOrCreate(
            ['email' => 'family@ministry.local'],
            [
                'name'          => 'أمين الأسرة',
                'password'      => Hash::make('Family@1234'),
                'personal_code' => '3333',
                'role'          => 'family_leader',
                'locale'        => 'ar',
                'is_active'     => true,
            ]
        );
        $familyLeader->syncRoles('family_leader');

        // servant
        $servant = User::updateOrCreate(
            ['email' => 'servant@ministry.local'],
            [
                'name'          => 'خادم',
                'password'      => Hash::make('Servant@1234'),
                'personal_code' => '4444',
                'role'          => 'servant',
                'locale'        => 'ar',
                'is_active'     => true,
            ]
        );
        $servant->syncRoles('servant');

        $this->command->info('✅ Users seeded:');
        $this->command->table(
            ['الدور', 'Email', 'Code'],
            [
                ['super_admin',    'admin@ministry.local',  '1111'],
                ['service_leader', 'leader@ministry.local', '2222'],
                ['family_leader',  'family@ministry.local', '3333'],
                ['servant',        'servant@ministry.local','4444'],
            ]
        );
    }
}
