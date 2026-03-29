<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // إعادة تعيين الـ cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'manage-users',
            'manage-service-groups',
            'view-all-beneficiaries',
            'view-group-beneficiaries',
            'view-own-beneficiaries',
            'create-beneficiary',
            'edit-beneficiary',
            'delete-beneficiary',
            'view-medical-data',
            'edit-medical-data',
            'view-family-data',
            'edit-family-data',
            'create-visit',
            'view-all-visits',
            'schedule-visit',
            'view-reports',
            'export-data',
            'view-audit-log',
            'manage-critical-cases',
            'change-language',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // super_admin — كل الصلاحيات
        $superAdmin = Role::firstOrCreate(['name' => UserRole::SuperAdmin->value]);
        $superAdmin->syncPermissions($permissions);

        // service_leader
        $serviceLeader = Role::firstOrCreate(['name' => UserRole::ServiceLeader->value]);
        $serviceLeader->syncPermissions([
            'view-all-beneficiaries', 'view-all-visits',
            'view-medical-data', 'view-family-data',
            'view-reports', 'export-data', 'view-audit-log',
            'manage-critical-cases', 'create-visit',
            'schedule-visit', 'change-language',
        ]);

        // family_leader
        $familyLeader = Role::firstOrCreate(['name' => UserRole::FamilyLeader->value]);
        $familyLeader->syncPermissions([
            'view-group-beneficiaries', 'create-beneficiary',
            'edit-beneficiary', 'view-medical-data', 'edit-medical-data',
            'view-family-data', 'edit-family-data', 'create-visit',
            'schedule-visit', 'view-reports', 'manage-critical-cases',
            'manage-service-groups', 'change-language',
        ]);

        // servant
        $servant = Role::firstOrCreate(['name' => UserRole::Servant->value]);
        $servant->syncPermissions([
            'view-own-beneficiaries', 'view-medical-data',
            'view-family-data', 'create-visit',
            'schedule-visit', 'change-language',
        ]);

        $this->command->info('✅ Roles and permissions seeded.');
    }
}   
