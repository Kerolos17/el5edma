<?php

namespace App\Filament\Resources\AuditLogs\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('users.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('model_type')
                    ->label(__('audit_logs.model'))
                    ->formatStateUsing(fn ($state) => class_basename($state))
                    ->badge()
                    ->color('gray'),

                TextColumn::make('model_id')
                    ->label(__('audit_logs.model_id'))
                    ->fontFamily('mono'),

                TextColumn::make('action')
                    ->label(__('audit_logs.action'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'created' => __('audit_logs.created'),
                        'updated' => __('audit_logs.updated'),
                        'deleted' => __('audit_logs.deleted'),
                        default   => $state,
                    }),

                TextColumn::make('ip_address')
                    ->label(__('audit_logs.ip_address'))
                    ->fontFamily('mono')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('beneficiaries.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label(__('audit_logs.action'))
                    ->options([
                        'created' => __('audit_logs.created'),
                        'updated' => __('audit_logs.updated'),
                        'deleted' => __('audit_logs.deleted'),
                    ]),

                SelectFilter::make('model_type')
                    ->label(__('audit_logs.model'))
                    ->options([
                        'App\\Models\\Beneficiary'    => __('audit_logs.model_beneficiary'),
                        'App\\Models\\Visit'          => __('audit_logs.model_visit'),
                        'App\\Models\\User'           => __('audit_logs.model_user'),
                        'App\\Models\\ServiceGroup'   => __('audit_logs.model_service_group'),
                        'App\\Models\\ScheduledVisit' => __('audit_logs.model_scheduled_visit'),
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('audit_logs.no_records'))
            ->emptyStateIcon('heroicon-o-magnifying-glass');
    }
}
