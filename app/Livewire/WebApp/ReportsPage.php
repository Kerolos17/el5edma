<?php

declare(strict_types=1);

namespace App\Livewire\WebApp;

use App\Enums\UserRole;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Support\WebAppScope;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;

#[Layout('web-app.layouts.app')]
class ReportsPage extends PlaceholderPage
{
    public function mount(string $section = 'reports'): void
    {
        $this->section = 'reports';
    }

    public function render(): View
    {
        $user = auth()->user();

        return view('livewire.web-app.reports-page', [
            'meta'                             => $this->meta(),
            'filters'                          => [],
            'stats'                            => $this->stats($user),
            'records'                          => collect(),
            'reportCards'                      => $this->reportCards($user),
            'beneficiaryOptions'               => collect(),
            'servantOptions'                   => collect(),
            'userRoleOptions'                  => collect(),
            'userServiceGroupOptions'          => collect(),
            'serviceGroupLeaderOptions'        => collect(),
            'serviceGroupServiceLeaderOptions' => collect(),
            'beneficiaryServiceGroupOptions'   => collect(),
            'beneficiaryServantOptions'        => collect(),
            'beneficiaryRecordStatusOptions'   => [],
            'medicalFileTypeOptions'           => [],
            'visitTypeOptions'                 => [],
            'beneficiaryStatusOptions'         => [],
        ]);
    }

    private function meta(): array
    {
        return [
            'title'           => __('web_app.resources.reports.title'),
            'description'     => __('web_app.resources.reports.description'),
            'icon'            => 'ph-chart-line-up',
            'primaryAction'   => ['label' => __('web_app.actions.dashboard'), 'route' => route('app.dashboard'), 'icon' => 'ph-squares-four'],
            'secondaryAction' => ['label' => __('web_app.actions.beneficiaries'), 'route' => route('app.beneficiaries'), 'icon' => 'ph-users-three'],
        ];
    }

    private function stats(User $user): array
    {
        return [
            ['label' => __('web_app.stats.available_reports'), 'value' => $this->reportCards($user)->count(), 'tone' => 'blue'],
            ['label' => __('web_app.stats.scoped_groups'), 'value' => $user->can('viewAny', ServiceGroup::class) ? WebAppScope::serviceGroups($user)->count() : 0, 'tone' => 'emerald'],
            ['label' => __('web_app.stats.scoped_beneficiaries'), 'value' => WebAppScope::beneficiaries($user)->count(), 'tone' => 'amber'],
        ];
    }

    private function reportCards(User $user): Collection
    {
        $cards = collect([
            [
                'title'       => __('web_app.reports.beneficiaries.title'),
                'description' => __('web_app.reports.beneficiaries.description'),
                'route'       => route('reports.beneficiaries.pdf'),
                'icon'        => 'ph-users-three',
            ],
            [
                'title'       => __('web_app.reports.beneficiaries_excel.title'),
                'description' => __('web_app.reports.beneficiaries_excel.description'),
                'route'       => route('reports.beneficiaries.excel'),
                'icon'        => 'ph-download-simple',
            ],
        ]);

        if (! in_array($user->role, [UserRole::SuperAdmin, UserRole::ServiceLeader, UserRole::FamilyLeader], true)) {
            return $cards;
        }

        $cards = $cards->merge([
            [
                'title'       => __('web_app.reports.visits.title'),
                'description' => __('web_app.reports.visits.description'),
                'route'       => route('reports.visits.pdf'),
                'icon'        => 'ph-clipboard-text',
            ],
            [
                'title'       => __('web_app.reports.visits_excel.title'),
                'description' => __('web_app.reports.visits_excel.description'),
                'route'       => route('reports.visits.excel'),
                'icon'        => 'ph-download-simple',
            ],
            [
                'title'       => __('web_app.reports.unvisited.title'),
                'description' => __('web_app.reports.unvisited.description'),
                'route'       => route('reports.unvisited.pdf'),
                'icon'        => 'ph-warning-circle',
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
                        'title'       => __('web_app.reports.service_group.title', ['name' => $group->name]),
                        'description' => __('web_app.reports.service_group.description'),
                        'route'       => route('reports.service-group.pdf', $group),
                        'icon'        => 'ph-tree-structure',
                    ],
                    [
                        'title'       => __('web_app.reports.service_group_beneficiaries.title', ['name' => $group->name]),
                        'description' => __('web_app.reports.service_group_beneficiaries.description'),
                        'route'       => route('reports.service-group.beneficiaries.pdf', $group),
                        'icon'        => 'ph-users-three',
                    ],
                ]),
        );
    }
}
