<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\ServiceGroup;
use App\Models\User;

class WebAppNavigation
{
    public static function items(User $user): array
    {
        return collect([
            ['route' => 'app.dashboard', 'icon' => 'ph-squares-four', 'label' => 'لوحة التحكم'],
            ['route' => 'app.beneficiaries', 'icon' => 'ph-users-three', 'label' => 'المخدومون'],
            ['route' => 'app.visits', 'icon' => 'ph-clipboard-text', 'label' => 'الزيارات'],
            ['route' => 'app.scheduled-visits', 'icon' => 'ph-calendar-check', 'label' => 'المجدولة'],
            ['route' => 'app.prayer-requests', 'icon' => 'ph-hands-praying', 'label' => 'طلبات الصلاة'],
            ['route' => 'app.medical-files', 'icon' => 'ph-file-lock', 'label' => 'الملفات الطبية'],
            ['route' => 'app.reports', 'icon' => 'ph-chart-line-up', 'label' => 'التقارير'],
            ['route' => 'app.users', 'icon' => 'ph-identification-card', 'label' => 'الخدام', 'can' => $user->can('viewAny', User::class)],
            ['route' => 'app.service-groups', 'icon' => 'ph-tree-structure', 'label' => 'مجموعات الخدمة', 'can' => $user->can('viewAny', ServiceGroup::class)],
        ])
            ->filter(fn (array $item) => $item['can'] ?? true)
            ->values()
            ->all();
    }
}
