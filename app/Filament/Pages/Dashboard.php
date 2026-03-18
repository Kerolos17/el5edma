<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\BirthdayWidget;
use App\Filament\Widgets\CriticalCasesWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\UnvisitedWidget;
use App\Filament\Widgets\VisitsChartWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getTitle(): string
    {
        return __('dashboard.title');
    }

    public function getWidgets(): array
    {
        return [
            StatsOverviewWidget::class,
            VisitsChartWidget::class,
            CriticalCasesWidget::class,
            BirthdayWidget::class,
            UnvisitedWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
