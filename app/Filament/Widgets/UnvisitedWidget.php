<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\Beneficiary;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class UnvisitedWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return __('dashboard.unvisited_title');
    }

    public function table(Table $table): Table
    {
        $user = Auth::user();

        // المخدومين اللي لم يُزاروا منذ أكثر من 30 يوماً
        $cutoff = now()->subDays(30)->toDateString();

        // Use single query with subquery for last visit date calculation
        $query = Beneficiary::query()
            ->with(['serviceGroup', 'assignedServant'])
            ->withMax('visits', 'visit_date')
            ->where('status', 'active')
            ->where(function ($q) use ($cutoff) {
                // Never visited beneficiaries using whereDoesntHave with indexed columns
                $q->whereDoesntHave('visits')
                    ->orWhere(function ($q2) use ($cutoff) {
                        // Beneficiaries with last visit before cutoff
                        $q2->whereHas('visits')
                            ->havingRaw('MAX(visits.visit_date) < ?', [$cutoff]);
                    });
            });

        if ($user->role === UserRole::FamilyLeader) {
            $query->where('service_group_id', $user->service_group_id);
        } elseif ($user->role === UserRole::Servant) {
            $query->where('assigned_servant_id', $user->id);
        }

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('full_name')
                    ->label(__('beneficiaries.full_name'))
                    ->searchable(),

                TextColumn::make('serviceGroup.name')
                    ->label(__('beneficiaries.service_group'))
                    ->badge()
                    ->color('info'),

                TextColumn::make('assignedServant.name')
                    ->label(__('beneficiaries.assigned_servant'))
                    ->default('—'),

                TextColumn::make('financial_status')
                    ->label(__('beneficiaries.financial_status'))
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'good'     => 'success',
                        'moderate' => 'warning',
                        'poor', 'very_poor' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => $state
                            ? __("beneficiaries.{$state}")
                            : '—',
                    ),

                TextColumn::make('visits_max_visit_date')
                    ->label(__('beneficiaries.last_visit'))
                    ->dateTime()
                    ->placeholder(__('beneficiaries.never_visited')),
            ])
            ->emptyStateHeading('✅')
            ->defaultSort('full_name');
    }
}
