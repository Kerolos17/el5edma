<?php

namespace App\Filament\Resources\PrayerRequests\Tables;

use App\Enums\UserRole;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PrayerRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('beneficiary.full_name')
                    ->label(__('visits.beneficiary'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->label(__('prayer.request_title'))
                    ->searchable()
                    ->limit(40),

                TextColumn::make('status')
                    ->label(__('prayer.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open'     => 'warning',
                        'answered' => 'success',
                        'closed'   => 'gray',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => __("prayer.{$state}")),

                TextColumn::make('createdBy.name')
                    ->label(__('beneficiaries.created_by'))
                    ->default('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('beneficiaries.created_at'))
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('answered_at')
                    ->label(__('prayer.answered_at'))
                    ->dateTime()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('prayer.status'))
                    ->options([
                        'open'     => __('prayer.open'),
                        'answered' => __('prayer.answered'),
                        'closed'   => __('prayer.closed'),
                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->visible(fn ($record) => Auth::user()->can('update', $record)),

                    Action::make('mark_answered')
                        ->label(__('prayer.mark_answered'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn ($record) => Auth::user()->can('update', $record) && $record->status === 'open')
                        ->action(fn ($record) => $record->update([
                            'status'      => 'answered',
                            'answered_at' => now(),
                        ])),

                    Action::make('mark_closed')
                        ->label(__('prayer.mark_closed'))
                        ->icon('heroicon-o-x-circle')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->visible(fn ($record) => Auth::user()->can('update', $record) && $record->status === 'open')
                        ->action(fn ($record) => $record->update([
                            'status' => 'closed',
                        ])),

                    DeleteAction::make()
                        ->visible(fn ($record) => Auth::user()->can('delete', $record)),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()?->role === UserRole::SuperAdmin),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
