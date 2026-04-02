<?php

namespace App\Filament\Widgets;

use App\Models\Beneficiary;
use App\Models\Visit;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = Auth::user();

        // ── Scope حسب الـ role ──
        $beneficiaryQuery = Beneficiary::query();
        $visitQuery       = Visit::query();

        if ($user->role === 'family_leader') {
            $beneficiaryQuery->where('service_group_id', $user->service_group_id);
            $visitQuery->whereHas('beneficiary', fn ($q) => $q->where('service_group_id', $user->service_group_id),
            );
        } elseif ($user->role === 'servant') {
            $beneficiaryQuery->where('assigned_servant_id', $user->id);
            $visitQuery->where('created_by', $user->id);
        }

        // Execute exactly 4 queries - one per statistic
        $totalBeneficiaries = $beneficiaryQuery
            ->where('status', 'active')
            ->count();

        $visitsThisWeek = (clone $visitQuery)
            ->where('type', 'home_visit')
            ->whereBetween('visit_date', [
                now()->copy()->startOfWeek(), now()->copy()->endOfWeek(),
            ])
            ->count();

        $callsThisWeek = (clone $visitQuery)
            ->where('type', 'phone_call')
            ->whereBetween('visit_date', [
                now()->copy()->startOfWeek(), now()->copy()->endOfWeek(),
            ])
            ->count();

        $criticalOpen = (clone $visitQuery)
            ->where('is_critical', true)
            ->whereNull('critical_resolved_at')
            ->count();

        return [
            Stat::make(__('dashboard.total_beneficiaries'), $totalBeneficiaries)
                ->icon('heroicon-o-users')
                ->color('primary'),

            Stat::make(__('dashboard.visits_this_week'), $visitsThisWeek)
                ->icon('heroicon-o-home')
                ->color('success'),

            Stat::make(__('dashboard.calls_this_week'), $callsThisWeek)
                ->icon('heroicon-o-phone')
                ->color('info'),

            Stat::make(__('dashboard.critical_cases'), $criticalOpen)
                ->icon('heroicon-o-exclamation-circle')
                ->color($criticalOpen > 0 ? 'danger' : 'success'),
        ];
    }
}
