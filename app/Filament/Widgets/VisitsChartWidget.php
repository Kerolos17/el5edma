<?php

namespace App\Filament\Widgets;

use App\Models\Visit;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class VisitsChartWidget extends ChartWidget
{
    protected static ?int $sort = 2;

    // protected ?string $maxHeight = '300px';

    public function getHeading(): string
    {
        return __('dashboard.visits_chart');
    }

    protected function getData(): array
    {
        $user   = Auth::user();
        $months = collect();

        // آخر 6 شهور
        for ($i = 5; $i >= 0; $i--) {
            $months->push(now()->subMonths($i));
        }

        $labels = [];
        $visits = [];
        $calls  = [];

        $arMonths = [
            1  => 'يناير', 2 => 'فبراير', 3 => 'مارس',
            4  => 'أبريل', 5 => 'مايو', 6 => 'يونيو',
            7  => 'يوليو', 8 => 'أغسطس', 9 => 'سبتمبر',
            10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
        ];

        // Build base query with role-based scoping
        $baseQuery = Visit::query();

        if ($user->role === 'family_leader') {
            $baseQuery->whereHas('beneficiary', fn ($q) => $q->where('service_group_id', $user->service_group_id),
            );
        } elseif ($user->role === 'servant') {
            $baseQuery->where('created_by', $user->id);
        }

        // Execute exactly 12 queries (6 months × 2 visit types)
        foreach ($months as $month) {
            $labels[] = App::isLocale('ar')
                ? $arMonths[$month->month]
                : $month->format('M Y');

            $monthQuery = (clone $baseQuery)
                ->whereYear('visit_date', $month->year)
                ->whereMonth('visit_date', $month->month);

            $visits[] = (clone $monthQuery)->where('type', 'home_visit')->count();
            $calls[]  = (clone $monthQuery)->where('type', 'phone_call')->count();
        }

        return [
            'datasets' => [
                [
                    'label'           => __('visits.home_visit'),
                    'data'            => $visits,
                    'backgroundColor' => 'rgba(42, 147, 147, 0.8)',
                    'borderColor'     => '#2A9393',
                    'borderWidth'     => 2,
                ],
                [
                    'label'           => __('visits.phone_call'),
                    'data'            => $calls,
                    'backgroundColor' => 'rgba(207, 146, 16, 0.8)',
                    'borderColor'     => '#CF9210',
                    'borderWidth'     => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
