<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\Visit;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VisitsChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    public function getHeading(): string
    {
        return __('dashboard.visits_chart');
    }

    protected function getData(): array
    {
        $user   = Auth::user();
        // $pageFilters is null until the dashboard filter form is submitted; default to 'week'
        $period = $this->filters['period'] ?? 'week';

        $baseQuery = Visit::query();

        if ($user->role === UserRole::FamilyLeader) {
            $baseQuery->whereHas('beneficiary', fn($q) =>
                $q->where('service_group_id', $user->service_group_id)
            );
        } elseif ($user->role === UserRole::Servant) {
            $baseQuery->where('created_by', $user->id);
        }

        return match ($period) {
            'week'  => $this->buildWeeklyData($baseQuery),
            'month' => $this->buildMonthlyData($baseQuery),
            default => $this->buildYearlyData($baseQuery),
        };
    }

    private function buildWeeklyData($baseQuery): array
    {
        $arDays = [
            0 => 'الأحد', 1 => 'الاثنين', 2 => 'الثلاثاء',
            3 => 'الأربعاء', 4 => 'الخميس', 5 => 'الجمعة', 6 => 'السبت',
        ];

        $days = collect();
        for ($i = 6; $i >= 0; $i--) {
            $days->push(now()->subDays($i));
        }

        $startDate = now()->subDays(6)->startOfDay();

        $rawData = (clone $baseQuery)
            ->selectRaw('DATE(visit_date) as day, type, COUNT(*) as total')
            ->where('visit_date', '>=', $startDate)
            ->groupByRaw('DATE(visit_date), type')
            ->get()
            ->groupBy('day');

        $labels = [];
        $visits = [];
        $calls  = [];

        foreach ($days as $day) {
            $labels[] = App::isLocale('ar')
                ? $arDays[$day->dayOfWeek]
                : $day->format('D d/m');

            $key   = $day->format('Y-m-d');
            $group = $rawData->get($key, collect());

            $visits[] = (int) ($group->where('type', 'home_visit')->first()?->total ?? 0);
            $calls[]  = (int) ($group->where('type', 'phone_call')->first()?->total ?? 0);
        }

        return $this->buildDatasets($labels, $visits, $calls);
    }

    private function buildMonthlyData($baseQuery): array
    {
        $weeks = collect();
        for ($i = 3; $i >= 0; $i--) {
            $weeks->push(now()->subWeeks($i));
        }

        $startDate = now()->subWeeks(3)->startOfWeek();

        $rawData = (clone $baseQuery)
            ->selectRaw($this->yearWeekSelect() . ', type, COUNT(*) as total')
            ->where('visit_date', '>=', $startDate)
            ->groupByRaw($this->yearWeekGroupBy() . ', type')
            ->get()
            ->groupBy('yw');

        $labels = [];
        $visits = [];
        $calls  = [];

        foreach ($weeks as $week) {
            $yw    = $week->format('oW'); // ISO year + week number
            $group = $rawData->get($yw, collect());

            $weekStart = $week->copy()->startOfWeek()->format('d/m');
            $weekEnd   = $week->copy()->endOfWeek()->format('d/m');
            $labels[]  = App::isLocale('ar')
                ? "أسبوع {$weekStart}"
                : "{$weekStart}–{$weekEnd}";

            $visits[] = (int) ($group->where('type', 'home_visit')->first()?->total ?? 0);
            $calls[]  = (int) ($group->where('type', 'phone_call')->first()?->total ?? 0);
        }

        return $this->buildDatasets($labels, $visits, $calls);
    }

    private function buildYearlyData($baseQuery): array
    {
        $arMonths = [
            1  => 'يناير', 2  => 'فبراير', 3  => 'مارس',
            4  => 'أبريل', 5  => 'مايو',   6  => 'يونيو',
            7  => 'يوليو', 8  => 'أغسطس',  9  => 'سبتمبر',
            10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
        ];

        $months = collect();
        for ($i = 11; $i >= 0; $i--) {
            $months->push(now()->subMonths($i));
        }

        $startDate = now()->subMonths(11)->startOfMonth();

        $rawData = (clone $baseQuery)
            ->selectRaw($this->yearMonthSelect() . ', type, COUNT(*) as total')
            ->where('visit_date', '>=', $startDate)
            ->groupByRaw($this->yearMonthGroupBy() . ', type')
            ->get()
            ->groupBy(fn($row) => $row->y . '-' . $row->m);

        $labels = [];
        $visits = [];
        $calls  = [];

        foreach ($months as $month) {
            $labels[] = App::isLocale('ar')
                ? $arMonths[$month->month]
                : $month->format('M Y');

            $key   = $month->year . '-' . $month->month;
            $group = $rawData->get($key, collect());

            $visits[] = (int) ($group->where('type', 'home_visit')->first()?->total ?? 0);
            $calls[]  = (int) ($group->where('type', 'phone_call')->first()?->total ?? 0);
        }

        return $this->buildDatasets($labels, $visits, $calls);
    }

    private function buildDatasets(array $labels, array $visits, array $calls): array
    {
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

    private function isSqlite(): bool
    {
        return DB::getDriverName() === 'sqlite';
    }

    private function yearWeekSelect(): string
    {
        return $this->isSqlite()
            ? "strftime('%Y%W', visit_date) as yw"
            : 'YEARWEEK(visit_date, 3) as yw';
    }

    private function yearWeekGroupBy(): string
    {
        return $this->isSqlite()
            ? "strftime('%Y%W', visit_date)"
            : 'YEARWEEK(visit_date, 3)';
    }

    private function yearMonthSelect(): string
    {
        return $this->isSqlite()
            ? "CAST(strftime('%Y', visit_date) AS INTEGER) as y, CAST(strftime('%m', visit_date) AS INTEGER) as m"
            : 'YEAR(visit_date) as y, MONTH(visit_date) as m';
    }

    private function yearMonthGroupBy(): string
    {
        return $this->isSqlite()
            ? "strftime('%Y', visit_date), strftime('%m', visit_date)"
            : 'YEAR(visit_date), MONTH(visit_date)';
    }
}
