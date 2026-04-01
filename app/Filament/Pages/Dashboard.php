<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\BirthdayWidget;
use App\Filament\Widgets\CriticalCasesWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\UnvisitedWidget;
use App\Filament\Widgets\VisitsChartWidget;
use Filament\Forms\Components\ToggleButtons;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function getTitle(): string
    {
        return __('dashboard.title');
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->schema([
            ToggleButtons::make('period')
                ->label(__('dashboard.period'))
                ->options([
                    'week'  => __('dashboard.week'),
                    'month' => __('dashboard.month'),
                    'year'  => __('dashboard.year'),
                ])
                ->default('week')
                ->inline()
                ->grouped(),
        ]);
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
