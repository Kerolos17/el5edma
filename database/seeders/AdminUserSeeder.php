<?php

namespace Database\Seeders;

use App\Enums\UserRole;
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
                'role'          => UserRole::SuperAdmin,
                'locale'        => 'ar',
                'is_active'     => true,
            ],
        );
        $admin->syncRoles(UserRole::SuperAdmin->value);

        // service_leader
        $leader = User::updateOrCreate(
            ['email' => 'leader@ministry.local'],
            [
                'name'          => 'أمين الخدمة',
                'password'      => Hash::make('Leader@1234'),
                'personal_code' => '2222',
                'role'          => UserRole::ServiceLeader,
                'locale'        => 'ar',
                'is_active'     => true,
            ],
        );
        $leader->syncRoles(UserRole::ServiceLeader->value);

        // family_leader
        $familyLeader = User::updateOrCreate(
            ['email' => 'family@ministry.local'],
            [
                'name'          => 'أمين الأسرة',
                'password'      => Hash::make('Family@1234'),
                'personal_code' => '3333',
                'role'          => UserRole::FamilyLeader,
                'locale'        => 'ar',
                'is_active'     => true,
            ],
        );
        $familyLeader->syncRoles(UserRole::FamilyLeader->value);

        // servant
        $servant = User::updateOrCreate(
            ['email' => 'servant@ministry.local'],
            [
                'name'          => 'خادم',
                'password'      => Hash::make('Servant@1234'),
                'personal_code' => '4444',
                'role'          => UserRole::Servant,
                'locale'        => 'ar',
                'is_active'     => true,
            ],
        );
        $servant->syncRoles(UserRole::Servant->value);

        $this->command->info('✅ Users seeded:');
        $this->command->table(
            ['الدور', 'Email', 'Code'],
            [
                ['super_admin',    'admin@ministry.local',  '1111'],
                ['service_leader', 'leader@ministry.local', '2222'],
                ['family_leader',  'family@ministry.local', '3333'],
                ['servant',        'servant@ministry.local', '4444'],
            ],
        );
    }
}
