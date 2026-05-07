<?php

declare(strict_types=1);

namespace App\Livewire\WebApp;

use App\Enums\UserRole;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Support\WebAppScope;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;

#[Layout('web-app.layouts.app')]
class ReportsPage extends PlaceholderPage
{
    public function mount(string $section = 'reports'): void
    {
        $this->section = 'reports';
    }

    public function render(): \Illuminate\View\View
    {
        $user = auth()->user();

        return view('livewire.web-app.placeholder-page', [
            'meta' => $this->meta(),
            'filters' => [],
            'stats' => $this->stats($user),
            'records' => collect(),
            'reportCards' => $this->reportCards($user),
            'beneficiaryOptions' => collect(),
            'servantOptions' => collect(),
            'userRoleOptions' => collect(),
            'userServiceGroupOptions' => collect(),
            'serviceGroupLeaderOptions' => collect(),
            'serviceGroupServiceLeaderOptions' => collect(),
            'beneficiaryServiceGroupOptions' => collect(),
            'beneficiaryServantOptions' => collect(),
            'beneficiaryRecordStatusOptions' => [],
            'medicalFileTypeOptions' => [],
            'visitTypeOptions' => [],
            'beneficiaryStatusOptions' => [],
        ]);
    }

    private function meta(): array
    {
        return [
            'title' => 'التقارير',
            'description' => 'مركز واحد لتقارير المتابعة والزيارات وملفات الأسر، مع روابط مباشرة للنسخ الحالية PDF.',
            'icon' => 'ph-chart-line-up',
            'primaryAction' => ['label' => 'لوحة التحكم', 'route' => route('app.dashboard'), 'icon' => 'ph-squares-four'],
            'secondaryAction' => ['label' => 'المخدومون', 'route' => route('app.beneficiaries'), 'icon' => 'ph-users-three'],
        ];
    }

    private function stats(User $user): array
    {
        return [
            ['label' => 'التقارير المتاحة', 'value' => $this->reportCards($user)->count(), 'tone' => 'blue'],
            ['label' => 'المجموعات ضمن نطاقك', 'value' => $user->can('viewAny', ServiceGroup::class) ? WebAppScope::serviceGroups($user)->count() : 0, 'tone' => 'emerald'],
            ['label' => 'المخدومون ضمن نطاقك', 'value' => WebAppScope::beneficiaries($user)->count(), 'tone' => 'amber'],
        ];
    }

    private function reportCards(User $user): Collection
    {
        $cards = collect([
            [
                'title' => 'تقرير المخدومين',
                'description' => 'نسخة PDF عامة لقائمة المخدومين حسب صلاحيات المستخدم الحالي.',
                'route' => route('reports.beneficiaries.pdf'),
                'icon' => 'ph-users-three',
            ],
        ]);

        if (! in_array($user->role, [UserRole::SuperAdmin, UserRole::ServiceLeader, UserRole::FamilyLeader], true)) {
            return $cards;
        }

        $cards = $cards->merge([
            [
                'title' => 'تقرير الزيارات',
                'description' => 'ملخص PDF للزيارات المسجلة.',
                'route' => route('reports.visits.pdf'),
                'icon' => 'ph-clipboard-text',
            ],
            [
                'title' => 'تقرير غير المزورين',
                'description' => 'إبراز المخدومين الذين يحتاجون متابعة أو زيارة.',
                'route' => route('reports.unvisited.pdf'),
                'icon' => 'ph-warning-circle',
            ],
        ]);

        if (! $user->can('viewAny', ServiceGroup::class)) {
            return $cards;
        }

        return $cards->merge(
            WebAppScope::serviceGroups($user)
                ->get()
                ->flatMap(fn (ServiceGroup $group) => [
                    [
                        'title' => "تقرير {$group->name}",
                        'description' => 'ملف PDF لبيانات الأسرة ومؤشراتها الأساسية.',
                        'route' => route('reports.service-group.pdf', $group),
                        'icon' => 'ph-tree-structure',
                    ],
                    [
                        'title' => "مخدومو {$group->name}",
                        'description' => 'قائمة PDF لمخدومي المجموعة الحالية.',
                        'route' => route('reports.service-group.beneficiaries.pdf', $group),
                        'icon' => 'ph-users-three',
                    ],
                ])
        );
    }
}
