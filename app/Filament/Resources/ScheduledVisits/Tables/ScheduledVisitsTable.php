<?php
namespace App\Filament\Resources\ScheduledVisits\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Auth;

class ScheduledVisitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('beneficiary.full_name')
                    ->label(__('visits.beneficiary'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('assignedServant.name')
                    ->label(__('beneficiaries.assigned_servant'))
                    ->default('—'),

                TextColumn::make('scheduled_date')
                    ->label(__('visits.scheduled_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('scheduled_time')
                    ->label(__('visits.scheduled_time')),

                TextColumn::make('status')
                    ->label(__('beneficiaries.status'))
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending'   => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn($state) => __("visits.{$state}")),

                TextColumn::make('reminder_sent_at')
                    ->label(__('visits.reminder_sent'))
                    ->dateTime()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('beneficiaries.status'))
                    ->options([
                        'pending'   => __('visits.pending'),
                        'completed' => __('visits.completed'),
                        'cancelled' => __('visits.cancelled'),
                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->visible(fn($record) => \App\Helpers\PermissionHelper::canModify()
                            && $record->status === 'pending'
                        ),
                    DeleteAction::make()
                        ->visible(fn($record) => \App\Helpers\PermissionHelper::canModify()
                            && $record->status !== 'completed'
                        ),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn() => Auth::user()?->role === UserRole::SuperAdmin),
                ]),
            ])
            ->defaultSort('scheduled_date', 'asc');
    }
}
