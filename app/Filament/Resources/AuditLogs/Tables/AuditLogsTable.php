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
                    ->label('Model')
                    ->formatStateUsing(fn ($state) => class_basename($state))
                    ->badge()
                    ->color('gray'),

                TextColumn::make('model_id')
                    ->label('ID')
                    ->fontFamily('mono'),

                TextColumn::make('action')
                    ->label('Action')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'created' => app()->getLocale() === 'ar' ? 'إنشاء' : 'Created',
                        'updated' => app()->getLocale() === 'ar' ? 'تعديل' : 'Updated',
                        'deleted' => app()->getLocale() === 'ar' ? 'حذف'   : 'Deleted',
                        default   => $state,
                    }),

                TextColumn::make('ip_address')
                    ->label('IP')
                    ->fontFamily('mono')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('beneficiaries.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('Action')
                    ->options([
                        'created' => app()->getLocale() === 'ar' ? 'إنشاء' : 'Created',
                        'updated' => app()->getLocale() === 'ar' ? 'تعديل' : 'Updated',
                        'deleted' => app()->getLocale() === 'ar' ? 'حذف'   : 'Deleted',
                    ]),

                SelectFilter::make('model_type')
                    ->label('Model')
                    ->options([
                        'App\\Models\\Beneficiary'    => 'Beneficiary',
                        'App\\Models\\Visit'          => 'Visit',
                        'App\\Models\\User'           => 'User',
                        'App\\Models\\ServiceGroup'   => 'ServiceGroup',
                        'App\\Models\\ScheduledVisit' => 'ScheduledVisit',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(
                app()->getLocale() === 'ar' ? 'لا توجد سجلات' : 'No records found'
            )
            ->emptyStateIcon('heroicon-o-magnifying-glass');
    }
}
