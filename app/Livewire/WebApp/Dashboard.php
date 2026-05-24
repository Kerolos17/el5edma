<?php

declare(strict_types=1);

namespace App\Livewire\WebApp;

use App\Models\Beneficiary;
use App\Models\MedicalFile;
use App\Models\PrayerRequest;
use App\Models\ScheduledVisit;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Models\Visit;
use App\Support\WebAppScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('web-app.layouts.app')]
class Dashboard extends Component
{
    public function render(): \Illuminate\View\View
    {
        $user = auth()->user();
        $beneficiaryScope = WebAppScope::beneficiaries($user);

        $stats = [
            [
                'label' => __('web_app.dashboard.stats.beneficiaries'),
                'value' => (clone $beneficiaryScope)->count(),
                'icon' => 'ph-users-three',
                'tone' => 'blue',
            ],
            [
                'label' => __('web_app.dashboard.stats.visits_this_month'),
                'value' => Visit::query()
                    ->whereHas('beneficiary', fn (Builder $query) => $query->whereIn('id', (clone $beneficiaryScope)->select('id')))
                    ->whereMonth('visit_date', now()->month)
                    ->whereYear('visit_date', now()->year)
                    ->count(),
                'icon' => 'ph-clipboard-text',
                'tone' => 'emerald',
            ],
            [
                'label' => __('web_app.dashboard.stats.scheduled_visits'),
                'value' => ScheduledVisit::query()
                    ->whereHas('beneficiary', fn (Builder $query) => $query->whereIn('id', (clone $beneficiaryScope)->select('id')))
                    ->where('scheduled_date', '>=', now()->toDateString())
                    ->where('status', '!=', 'completed')
                    ->count(),
                'icon' => 'ph-calendar-check',
                'tone' => 'amber',
            ],
            [
                'label' => __('web_app.dashboard.stats.critical_cases'),
                'value' => (clone $beneficiaryScope)->where('health_status', 'critical')->count(),
                'icon' => 'ph-warning-circle',
                'tone' => 'rose',
            ],
        ];

        $secondaryStats = [
            'openPrayerRequests' => PrayerRequest::query()
                ->whereHas('beneficiary', fn (Builder $query) => $query->whereIn('id', (clone $beneficiaryScope)->select('id')))
                ->where('status', 'open')
                ->count(),
            'medicalFiles' => MedicalFile::query()
                ->whereHas('beneficiary', fn (Builder $query) => $query->whereIn('id', (clone $beneficiaryScope)->select('id')))
                ->count(),
            'users' => $user->can('viewAny', User::class) ? WebAppScope::users($user)->count() : null,
            'serviceGroups' => $user->can('viewAny', ServiceGroup::class) ? WebAppScope::serviceGroups($user)->count() : null,
        ];

        $recentVisits = WebAppScope::visits($user)
            ->latest('visit_date')
            ->limit(6)
            ->get();

        $todayBirthdays = (clone $beneficiaryScope)
            ->with('serviceGroup')
            ->whereMonth('birth_date', now()->month)
            ->whereDay('birth_date', now()->day)
            ->limit(10)
            ->get();

        $unvisited = (clone $beneficiaryScope)
            ->with('serviceGroup')
            ->whereDoesntHave('visits', fn (Builder $query) => $query->where('visit_date', '>=', now()->subDays(30)))
            ->limit(5)
            ->get();

        $criticalCases = (clone $beneficiaryScope)
            ->with('serviceGroup')
            ->where('health_status', 'critical')
            ->limit(5)
            ->get();

        $visitsChart = Visit::query()
            ->whereHas('beneficiary', fn (Builder $query) => $query->whereIn('id', (clone $beneficiaryScope)->select('id')))
            ->where('visit_date', '>=', now()->subMonths(6)->startOfMonth())
            ->select(DB::raw("DATE_FORMAT(visit_date, '%Y-%m') as month"), DB::raw('count(*) as total'))
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        return view('livewire.web-app.dashboard', [
            'title' => __('web_app.dashboard.title'),
            'roleLabel' => WebAppScope::roleLabel($user->role),
            'stats' => $stats,
            'secondaryStats' => $secondaryStats,
            'recentVisits' => $recentVisits,
            'todayBirthdays' => $todayBirthdays,
            'unvisited' => $unvisited,
            'criticalCases' => $criticalCases,
            'visitsChart' => $visitsChart,
        ]);
    }
}
