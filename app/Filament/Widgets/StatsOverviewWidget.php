<?php

namespace App\Filament\Widgets;

use App\Models\Beneficiary;
use App\Models\Visit;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class StatsOverviewWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user   = Auth::user();
        // $pageFilters is null until the dashboard filter form is submitted; default to 'week'
        $period = $this->filters['period'] ?? 'week';

        $cacheKey = "dashboard:stats:{$user->id}:{$period}";

        $stats = Cache::remember($cacheKey, 300, function () use ($user, $period) {
            [$currentStart, $currentEnd, $previousStart, $previousEnd] = $this->getPeriodBounds($period);

            $beneficiaryQuery = Beneficiary::query();
            $visitQuery       = Visit::query();

            if ($user->role === UserRole::FamilyLeader) {
                $beneficiaryQuery->where('service_group_id', $user->service_group_id);
                $visitQuery->whereHas('beneficiary', fn($q) =>
                    $q->where('service_group_id', $user->service_group_id)
                );
            } elseif ($user->role === UserRole::Servant) {
                $beneficiaryQuery->where('assigned_servant_id', $user->id);
                $visitQuery->where('created_by', $user->id);
            }

            $currentVisits = (clone $visitQuery)->where('type', 'home_visit')
                ->whereBetween('visit_date', [$currentStart, $currentEnd])->count();
            $currentCalls  = (clone $visitQuery)->where('type', 'phone_call')
                ->whereBetween('visit_date', [$currentStart, $currentEnd])->count();

            $previousVisits = (clone $visitQuery)->where('type', 'home_visit')
                ->whereBetween('visit_date', [$previousStart, $previousEnd])->count();
            $previousCalls  = (clone $visitQuery)->where('type', 'phone_call')
                ->whereBetween('visit_date', [$previousStart, $previousEnd])->count();

            return [
                'beneficiaries'  => $beneficiaryQuery->where('status', 'active')->count(),
                'visits'         => $currentVisits,
                'calls'          => $currentCalls,
                'critical'       => (clone $visitQuery)->where('is_critical', true)
                    ->whereNull('critical_resolved_at')->count(),
                'prev_visits'    => $previousVisits,
                'prev_calls'     => $previousCalls,
            ];
        });

        return [
            Stat::make(__('dashboard.total_beneficiaries'), $stats['beneficiaries'])
                ->icon('heroicon-o-users')
                ->color('primary'),

            Stat::make($this->getPeriodLabel('visits', $period), $stats['visits'])
                ->icon('heroicon-o-home')
                ->color('success')
                ->description($this->getTrendDescription($stats['visits'], $stats['prev_visits']))
                ->descriptionIcon($this->getTrendIcon($stats['visits'], $stats['prev_visits']))
                ->descriptionColor($this->getTrendColor($stats['visits'], $stats['prev_visits'])),

            Stat::make($this->getPeriodLabel('calls', $period), $stats['calls'])
                ->icon('heroicon-o-phone')
                ->color('info')
                ->description($this->getTrendDescription($stats['calls'], $stats['prev_calls']))
                ->descriptionIcon($this->getTrendIcon($stats['calls'], $stats['prev_calls']))
                ->descriptionColor($this->getTrendColor($stats['calls'], $stats['prev_calls'])),

            Stat::make(__('dashboard.critical_cases'), $stats['critical'])
                ->icon('heroicon-o-exclamation-circle')
                ->color($stats['critical'] > 0 ? 'danger' : 'success'),
        ];
    }

    private function getPeriodBounds(string $period): array
    {
        return match ($period) {
            'month' => [
                now()->startOfMonth(),
                now()->endOfMonth(),
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth(),
            ],
            'year' => [
                now()->startOfYear(),
                now()->endOfYear(),
                now()->subYear()->startOfYear(),
                now()->subYear()->endOfYear(),
            ],
            default => [ // week
                now()->startOfWeek(),
                now()->endOfWeek(),
                now()->subWeek()->startOfWeek(),
                now()->subWeek()->endOfWeek(),
            ],
        };
    }

    private function getPeriodLabel(string $type, string $period): string
    {
        $periodLabel = __("dashboard.{$period}");
        return $type === 'visits'
            ? __('dashboard.visits_period', ['period' => $periodLabel])
            : __('dashboard.calls_period', ['period' => $periodLabel]);
    }

    private function getTrendDescription(int $current, int $previous): string
    {
        if ($previous === 0) {
            return $current > 0
                ? __('dashboard.increase', ['percent' => 100])
                : __('dashboard.no_change');
        }

        $percent = round(abs($current - $previous) / $previous * 100);

        if ($current > $previous) {
            return __('dashboard.increase', ['percent' => $percent]);
        } elseif ($current < $previous) {
            return __('dashboard.decrease', ['percent' => $percent]);
        }

        return __('dashboard.no_change');
    }

    private function getTrendIcon(int $current, int $previous): ?string
    {
        if ($current > $previous) return 'heroicon-m-arrow-trending-up';
        if ($current < $previous) return 'heroicon-m-arrow-trending-down';
        return null;
    }

    private function getTrendColor(int $current, int $previous): string
    {
        if ($current > $previous) return 'success';
        if ($current < $previous) return 'danger';
        return 'gray';
    }
}
