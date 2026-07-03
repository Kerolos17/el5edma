<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\AuditLog;
use App\Models\ServiceGroup;
use App\Models\User;

class WebAppNavigation
{
    public static function items(User $user): array
    {
        return collect([
            // Overview
            ['route' => 'app.dashboard', 'icon' => 'ph-squares-four', 'label' => __('web_app.navigation.dashboard'), 'group' => 'overview'],

            // Ministry
            ['route' => 'app.visits', 'icon' => 'ph-clipboard-text', 'label' => __('web_app.navigation.visits'), 'group' => 'work'],
            ['route' => 'app.scheduled-visits', 'icon' => 'ph-calendar-check', 'label' => __('web_app.navigation.scheduled_visits'), 'group' => 'work'],
            ['route' => 'app.prayer-requests', 'icon' => 'ph-hands-praying', 'label' => __('web_app.navigation.prayer_requests'), 'group' => 'work'],

            // People
            ['route' => 'app.beneficiaries', 'icon' => 'ph-users-three', 'label' => __('web_app.navigation.beneficiaries'), 'group' => 'people'],
            ['route' => 'app.users', 'icon' => 'ph-identification-card', 'label' => __('web_app.navigation.users'), 'group' => 'people', 'can' => $user->can('viewAny', User::class)],

            // Records
            ['route' => 'app.medical-files', 'icon' => 'ph-file-lock', 'label' => __('web_app.navigation.medical_files'), 'group' => 'records'],
            ['route' => 'app.reports', 'icon' => 'ph-chart-line-up', 'label' => __('web_app.navigation.reports'), 'group' => 'records'],
            ['route' => 'app.service-groups', 'icon' => 'ph-tree-structure', 'label' => __('web_app.navigation.service_groups'), 'group' => 'records', 'can' => $user->can('viewAny', ServiceGroup::class)],

            // System
            ['route' => 'app.notifications', 'icon' => 'ph-bell', 'label' => __('web_app.navigation.notifications'), 'group' => 'system'],
            ['route' => 'app.audit-logs', 'icon' => 'ph-clipboard-text', 'label' => __('web_app.navigation.audit_logs'), 'group' => 'system', 'can' => $user->can('viewAny', AuditLog::class)],

            // Account (ungrouped, at bottom)
            ['route' => 'app.profile', 'icon' => 'ph-user-circle', 'label' => __('web_app.navigation.profile')],
        ])
            ->filter(fn (array $item) => $item['can'] ?? true)
            ->values()
            ->all();
    }
}
