<?php
namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use App\Services\CacheService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Auth;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('users.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label(__('users.email'))
                    ->searchable()
                    ->copyable(),

                TextColumn::make('role')
                    ->label(__('users.role'))
                    ->badge()
                    ->color(fn(UserRole $state): string => match ($state) {
                        UserRole::SuperAdmin     => 'danger',
                        UserRole::ServiceLeader  => 'warning',
                        UserRole::FamilyLeader   => 'info',
                        UserRole::Servant        => 'success',
                        default                  => 'gray',
                    })
                    ->formatStateUsing(fn(UserRole $state): string => __("users.roles.{$state->value}")),

                TextColumn::make('serviceGroup.name')
                    ->label(__('users.service_group'))
                    ->placeholder('-'),

                TextColumn::make('personal_code')
                    ->label(__('users.personal_code'))
                    ->fontFamily('mono')
                    ->copyable()
                    ->visible(fn() => Auth::user()?->role === UserRole::SuperAdmin),

                IconColumn::make('is_active')
                    ->label(__('users.is_active'))
                    ->boolean(),

                TextColumn::make('last_login_at')
                    ->label(__('users.last_login_at'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label(__('users.role'))
                    ->options([
                        'super_admin'    => __('users.super_admin'),
                        'service_leader' => __('users.service_leader'),
                        'family_leader'  => __('users.family_leader'),
                        'servant'        => __('users.servant'),
                    ]),

                SelectFilter::make('service_group_id')
                    ->label(__('users.service_group'))
                    ->options(fn() => CacheService::getServiceGroups()),

                TernaryFilter::make('is_active')
                    ->label(__('users.is_active')),

                TernaryFilter::make('pending_approval')
                    ->label(__('users.pending_approval'))
                    ->placeholder(__('users.all_servants'))
                    ->trueLabel(__('users.pending_only'))
                    ->falseLabel(__('users.approved_only'))
                    ->queries(
                        true: fn($query)  => $query->where('role', UserRole::Servant)->where('is_active', false),
                        false: fn($query) => $query->where('role', UserRole::Servant)->where('is_active', true),
                        blank: fn($query) => $query,
                    ),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),

                    Action::make('approve_servant')
                        ->label(__('users.approve_servant'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn(User $record) =>
                            ! $record->is_active &&
                            $record->role === UserRole::Servant &&
                            Auth::user()->can('update', $record)
                        )
                        ->requiresConfirmation()
                        ->modalHeading(__('users.approve_servant_confirmation'))
                        ->modalDescription(__('users.approve_servant_description'))
                        ->action(function (User $record) {
                            $record->update(['is_active' => true]);

                            Notification::make()
                                ->title(__('users.servant_approved'))
                                ->success()
                                ->send();
                        }),

                    Action::make('generate_code')
                        ->label(__('users.generate_code'))
                        ->icon('heroicon-o-key')
                        ->color('warning')
                        ->visible(fn() => Auth::user()?->role === UserRole::SuperAdmin)
                        ->requiresConfirmation()
                        ->action(function (User $record) {
                            do {
                                $code = (string) random_int(1000, 999999);
                                $hash = hash('sha256', $code);
                            } while (User::where('personal_code_hash', $hash)->exists());

                            $record->update(['personal_code' => $code]);

                            Notification::make()
                                ->title(__('users.personal_code') . ': ' . $code)
                                ->success()
                                ->send();
                        }),

                    DeleteAction::make()
                        ->before(function (User $record, DeleteAction $action) {
                            if ($record->id === Auth::id()) {
                                Notification::make()
                                    ->title(__('users.cannot_delete_self'))
                                    ->danger()
                                    ->send();

                                $action->cancel();
                            }
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
