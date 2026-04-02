<?php

namespace App\Filament\Widgets;

use App\Models\Visit;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Auth;

class CriticalCasesWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return __('dashboard.critical_cases');
    }

    public function table(Table $table): Table
    {
        $user = Auth::user();

        $query = Visit::with(['beneficiary', 'createdBy'])
            ->where('is_critical', true)
            ->whereNull('critical_resolved_at');

        if ($user->role === UserRole::FamilyLeader) {
            $query->whereHas('beneficiary', fn ($q) =>
                $q->where('service_group_id', $user->service_group_id)
            );
        } elseif ($user->role === UserRole::Servant) {
            $query->where('created_by', $user->id);
        }

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('beneficiary.full_name')
                    ->label(__('visits.beneficiary'))
                    ->searchable(),

                TextColumn::make('visit_date')
                    ->label(__('visits.visit_date'))
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('feedback')
                    ->label(__('visits.feedback'))
                    ->limit(60)
                    ->placeholder('—'),

                TextColumn::make('createdBy.name')
                    ->label(__('beneficiaries.created_by'))
                    ->default('—'),
            ])
            ->recordActions([
                Action::make('resolve')
                    ->label(__('dashboard.mark_resolved'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn () => in_array(Auth::user()?->role, [
                        UserRole::SuperAdmin, UserRole::ServiceLeader, UserRole::FamilyLeader,
                    ]))
                    ->action(fn ($record) => $record->update([
                        'critical_resolved_at' => now(),
                        'critical_resolved_by' => Auth::id(),
                    ])),

                ViewAction::make()
                    ->url(fn ($record) => route('filament.admin.resources.visits.view', $record)),
            ])
            ->emptyStateHeading(__('dashboard.no_critical_cases'))
            ->emptyStateIcon('heroicon-o-check-circle')
            ->defaultSort('visit_date', 'desc');
    }
}
