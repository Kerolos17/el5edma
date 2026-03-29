<?php
namespace App\Filament\Widgets;

use App\Models\Beneficiary;
use App\Models\Visit;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user     = Auth::user();
        $cacheKey = "dashboard:stats:{$user->id}";

        $stats = Cache::remember($cacheKey, 300, function () use ($user) {
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

            $weekStart = now()->copy()->startOfWeek();
            $weekEnd   = now()->copy()->endOfWeek();

            return [
                'beneficiaries' => $beneficiaryQuery->where('status', 'active')->count(),
                'visits'        => (clone $visitQuery)->where('type', 'home_visit')
                    ->whereBetween('visit_date', [$weekStart, $weekEnd])->count(),
                'calls'         => (clone $visitQuery)->where('type', 'phone_call')
                    ->whereBetween('visit_date', [$weekStart, $weekEnd])->count(),
                'critical'      => (clone $visitQuery)->where('is_critical', true)
                    ->whereNull('critical_resolved_at')->count(),
            ];
        });

        return [
            Stat::make(__('dashboard.total_beneficiaries'), $stats['beneficiaries'])
                ->icon('heroicon-o-users')
                ->color('primary'),

            Stat::make(__('dashboard.visits_this_week'), $stats['visits'])
                ->icon('heroicon-o-home')
                ->color('success'),

            Stat::make(__('dashboard.calls_this_week'), $stats['calls'])
                ->icon('heroicon-o-phone')
                ->color('info'),

            Stat::make(__('dashboard.critical_cases'), $stats['critical'])
                ->icon('heroicon-o-exclamation-circle')
                ->color($stats['critical'] > 0 ? 'danger' : 'success'),
        ];
    }
}
