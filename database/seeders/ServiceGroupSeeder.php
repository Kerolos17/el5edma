<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\ServiceGroup;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ServiceGroupSeeder extends Seeder
{
    public function run(): void
    {
        // ── Service Leader (existing) ──────────────────────────────────────────
        $serviceLeader = User::updateOrCreate(
            ['email' => 'leader@ministry.local'],
            ['role' => UserRole::ServiceLeader, 'is_active' => true],
        );

        // ── Family Leaders ─────────────────────────────────────────────────────
        $familyLeader1 = User::updateOrCreate(
            ['email' => 'family@ministry.local'],
            [
                'name'          => 'مينا رمزي',
                'role'          => UserRole::FamilyLeader,
                'personal_code' => '3333',
                'locale'        => 'ar',
                'is_active'     => true,
            ],
        );

        $familyLeader2 = User::updateOrCreate(
            ['email' => 'family2@ministry.local'],
            [
                'name'          => 'بيشوي سامي',
                'password'      => Hash::make('Family@1234'),
                'personal_code' => '3334',
                'role'          => UserRole::FamilyLeader,
                'locale'        => 'ar',
                'is_active'     => true,
            ],
        );

        $familyLeader3 = User::updateOrCreate(
            ['email' => 'family3@ministry.local'],
            [
                'name'          => 'جورج نادر',
                'password'      => Hash::make('Family@1234'),
                'personal_code' => '3335',
                'role'          => UserRole::FamilyLeader,
                'locale'        => 'ar',
                'is_active'     => true,
            ],
        );

        // ── Servants ───────────────────────────────────────────────────────────
        $servantData = [
            ['email' => 'servant@ministry.local',  'name' => 'كيرلس فارس',   'code' => '4444'],
            ['email' => 'servant2@ministry.local', 'name' => 'مارك إبراهيم', 'code' => '5001'],
            ['email' => 'servant3@ministry.local', 'name' => 'صموئيل حنا',   'code' => '5002'],
            ['email' => 'servant4@ministry.local', 'name' => 'ماريان نبيل',  'code' => '5003'],
            ['email' => 'servant5@ministry.local', 'name' => 'ميلاد عزيز',   'code' => '5004'],
            ['email' => 'servant6@ministry.local', 'name' => 'ريهام فهمي',   'code' => '5005'],
        ];

        $servants = [];
        foreach ($servantData as $data) {
            $servants[] = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'          => $data['name'],
                    'password'      => Hash::make('Servant@1234'),
                    'personal_code' => $data['code'],
                    'role'          => UserRole::Servant,
                    'locale'        => 'ar',
                    'is_active'     => true,
                ],
            );
        }

        // ── Service Groups ─────────────────────────────────────────────────────
        $group1 = ServiceGroup::updateOrCreate(
            ['name' => 'أسرة النور'],
            [
                'description'       => 'أسرة خدمة حي مصر الجديدة والمنطقة المحيطة بها',
                'leader_id'         => $familyLeader1->id,
                'service_leader_id' => $serviceLeader->id,
                'is_active'         => true,
            ],
        );

        $group2 = ServiceGroup::updateOrCreate(
            ['name' => 'أسرة الرجاء'],
            [
                'description'       => 'أسرة خدمة حي شبرا والقاهرة الشمالية',
                'leader_id'         => $familyLeader2->id,
                'service_leader_id' => $serviceLeader->id,
                'is_active'         => true,
            ],
        );

        $group3 = ServiceGroup::updateOrCreate(
            ['name' => 'أسرة الفرح'],
            [
                'description'       => 'أسرة خدمة حي الجيزة والمنطقة الغربية',
                'leader_id'         => $familyLeader3->id,
                'service_leader_id' => $serviceLeader->id,
                'is_active'         => true,
            ],
        );

        // ── Assign servants to their groups ───────────────────────────────────
        $servants[0]->update(['service_group_id' => $group1->id]); // كيرلس  → النور
        $servants[1]->update(['service_group_id' => $group1->id]); // مارك   → النور
        $servants[2]->update(['service_group_id' => $group2->id]); // صموئيل → الرجاء
        $servants[3]->update(['service_group_id' => $group2->id]); // ماريان → الرجاء
        $servants[4]->update(['service_group_id' => $group3->id]); // ميلاد  → الفرح
        $servants[5]->update(['service_group_id' => $group3->id]); // ريهام  → الفرح

        // Assign family leaders to their own groups
        $familyLeader1->update(['service_group_id' => $group1->id]);
        $familyLeader2->update(['service_group_id' => $group2->id]);
        $familyLeader3->update(['service_group_id' => $group3->id]);

        $this->command->info('✅ Service groups seeded:');
        $this->command->table(
            ['الأسرة', 'أمين الأسرة', 'عدد الخدام'],
            [
                ['أسرة النور',   'مينا رمزي',   '2 (كيرلس + مارك)'],
                ['أسرة الرجاء',  'بيشوي سامي',  '2 (صموئيل + ماريان)'],
                ['أسرة الفرح',   'جورج نادر',   '2 (ميلاد + ريهام)'],
            ],
        );

        $this->command->info('');
        $this->command->info('👤 Servant login codes:');
        $this->command->table(
            ['الاسم', 'Email', 'Code', 'الأسرة'],
            [
                ['كيرلس فارس',   'servant@ministry.local',  '4444', 'أسرة النور'],
                ['مارك إبراهيم', 'servant2@ministry.local', '5001', 'أسرة النور'],
                ['صموئيل حنا',   'servant3@ministry.local', '5002', 'أسرة الرجاء'],
                ['ماريان نبيل',  'servant4@ministry.local', '5003', 'أسرة الرجاء'],
                ['ميلاد عزيز',   'servant5@ministry.local', '5004', 'أسرة الفرح'],
                ['ريهام فهمي',   'servant6@ministry.local', '5005', 'أسرة الفرح'],
            ],
        );
    }
}
