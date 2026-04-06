<?php
namespace App\Filament\Resources\MinistryNotifications\Tables;

use App\Models\MinistryNotification;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class MinistryNotificationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label(__('notifications.title'))
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'birthday'           => 'warning',
                        'critical_case'      => 'danger',
                        'visit_reminder'     => 'info',
                        'unvisited_alert'    => 'warning',
                        'new_beneficiary'    => 'success',
                        'servant_registered' => 'info',
                        default              => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'birthday'           => '🎂 ' . __('notifications.birthday_title'),
                        'critical_case'      => '🔴 ' . __('notifications.critical_case_title'),
                        'visit_reminder'     => '📅 ' . __('notifications.visit_reminder_title'),
                        'unvisited_alert'    => '⏰ ' . __('notifications.unvisited_alert_title'),
                        'new_beneficiary'    => '✨ ' . __('notifications.new_beneficiary_title'),
                        'servant_registered' => '👋 ' . __('notifications.servant_registered.title'),
                        default              => $state,
                    }),

                TextColumn::make('title')
                    ->label(__('notifications.title'))
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('body')
                    ->label('')
                    ->limit(80)
                    ->color('gray'),

                IconColumn::make('read_at')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-o-envelope-open')
                    ->falseIcon('heroicon-o-envelope')
                    ->trueColor('gray')
                    ->falseColor('primary'),

                TextColumn::make('created_at')
                    ->label(__('beneficiaries.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('notifications.title'))
                    ->options([
                        'birthday'           => __('notifications.birthday_title'),
                        'critical_case'      => __('notifications.critical_case_title'),
                        'visit_reminder'     => __('notifications.visit_reminder_title'),
                        'unvisited_alert'    => __('notifications.unvisited_alert_title'),
                        'new_beneficiary'    => __('notifications.new_beneficiary_title'),
                        'servant_registered' => __('notifications.servant_registered.title'),
                    ]),

                TernaryFilter::make('read_at')
                    ->label(__('notifications.mark_all_read'))
                    ->nullable()
                    ->trueLabel(__('notifications.read'))
                    ->falseLabel(__('notifications.unread')),
            ])
            ->recordActions([
                Action::make('mark_read')
                    ->label(__('notifications.mark_all_read'))
                    ->icon('heroicon-o-check')
                    ->color('gray')
                    ->visible(fn($record) => is_null($record->read_at) && $record->user_id === Auth::id())
                    ->action(fn($record) => $record->user_id === Auth::id() ? $record->update(['read_at' => now()]) : null),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    Action::make('mark_all_read')
                        ->label(__('notifications.mark_all_read'))
                        ->icon('heroicon-o-check-circle')
                        ->action(function () {
                            MinistryNotification::where('user_id', Auth::id())
                                ->whereNull('read_at')
                                ->update(['read_at' => now()]);
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
