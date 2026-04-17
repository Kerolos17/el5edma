<?php

namespace App\Filament\Resources\Visits\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Auth;

class VisitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('beneficiary.full_name')
                    ->label(__('visits.beneficiary'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label(__('visits.type'))
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => __("visits.{$state}")),

                TextColumn::make('visit_date')
                    ->label(__('visits.visit_date'))
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('beneficiary_status')
                    ->label(__('visits.beneficiary_status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'great'        => 'success',
                        'good'         => 'info',
                        'needs_follow' => 'warning',
                        'critical'     => 'danger',
                        default        => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => __("visits.{$state}")),

                IconColumn::make('is_critical')
                    ->label(__('visits.is_critical'))
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('gray'),

                TextColumn::make('createdBy.name')
                    ->label(__('beneficiaries.created_by'))
                    ->default('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('duration_minutes')
                    ->label(__('visits.duration_minutes'))
                    ->default('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('visits.type'))
                    ->options([
                        'home_visit'     => __('visits.home_visit'),
                        'phone_call'     => __('visits.phone_call'),
                        'church_meeting' => __('visits.church_meeting'),
                    ]),

                SelectFilter::make('beneficiary_status')
                    ->label(__('visits.beneficiary_status'))
                    ->options([
                        'great'        => __('visits.great'),
                        'good'         => __('visits.good'),
                        'needs_follow' => __('visits.needs_follow'),
                        'critical'     => __('visits.critical'),
                    ]),

                TernaryFilter::make('is_critical')
                    ->label(__('visits.is_critical')),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->visible(fn ($record) => Auth::user()->can('update', $record)
                            && (! $record->is_critical
                                || in_array(Auth::user()?->role, [UserRole::SuperAdmin, UserRole::ServiceLeader, UserRole::FamilyLeader]))
                        ),

                    // إغلاق الحالة الحرجة
                    Action::make('resolve_critical')
                        ->label(__('visits.critical_resolved'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn ($record) => $record->is_critical
                            && is_null($record->critical_resolved_at)
                            && in_array(Auth::user()?->role, [UserRole::SuperAdmin, UserRole::ServiceLeader, UserRole::FamilyLeader])
                        )
                        ->action(function ($record) {
                            $record->update([
                                'critical_resolved_at' => now(),
                                'critical_resolved_by' => Auth::id(),
                            ]);
                        }),

                    DeleteAction::make()
                        ->visible(fn($record) => ! $record->is_critical
                            && Auth::user()?->role === UserRole::SuperAdmin
                        ),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn() => Auth::user()?->role === UserRole::SuperAdmin),
                ]),
            ])
            ->defaultSort('visit_date', 'desc')
            ->emptyStateHeading(__('visits.no_records'))
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }
}
