<?php

namespace App\Filament\Resources\ServiceGroups\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Auth;

class ServiceGroupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('service_groups.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('leader.name')
                    ->label(__('service_groups.leader'))
                    ->placeholder('-'),

                TextColumn::make('serviceLeader.name')
                    ->label(__('service_groups.service_leader'))
                    ->placeholder('-'),

                TextColumn::make('servants_count')
                    ->label(__('service_groups.servants_count'))
                    ->counts('servants')
                    ->badge()
                    ->color('info'),

                TextColumn::make('beneficiaries_count')
                    ->label(__('service_groups.beneficiaries_count'))
                    ->counts('beneficiaries')
                    ->badge()
                    ->color('success'),

                IconColumn::make('is_active')
                    ->label(__('service_groups.is_active'))
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label(__('beneficiaries.created_at'))
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('service_groups.is_active')),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->visible(fn ($record) => Auth::user()->can('update', $record)),
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
            ->defaultSort('name');
    }
}
