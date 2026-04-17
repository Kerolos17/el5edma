<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make(__('users.title'))
                ->schema([
                    ImageEntry::make('profile_photo')
                        ->label(__('users.profile_photo'))
                        ->disk('public')
                        ->circular()
                        ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&size=96')
                        ->columnSpanFull(),

                    TextEntry::make('name')
                        ->label(__('users.name')),

                    TextEntry::make('email')
                        ->label(__('users.email'))
                        ->copyable(),

                    TextEntry::make('phone')
                        ->label(__('users.phone'))
                        ->placeholder('-'),

                    TextEntry::make('personal_code')
                        ->label(__('users.personal_code'))
                        ->fontFamily('mono')
                        ->placeholder('-')
                        ->visible(fn () => Auth::user()?->role === UserRole::SuperAdmin),
                ])->columns(2),

            Section::make(__('users.role'))
                ->schema([
                    TextEntry::make('role')
                        ->label(__('users.role'))
                        ->badge()
                        ->color(fn (UserRole $state): string => match ($state) {
                            UserRole::SuperAdmin    => 'danger',
                            UserRole::ServiceLeader => 'warning',
                            UserRole::FamilyLeader  => 'info',
                            UserRole::Servant       => 'success',
                            default                 => 'gray',
                        })
                        ->formatStateUsing(fn (UserRole $state): string => __("users.roles.{$state->value}")),

                    TextEntry::make('serviceGroup.name')
                        ->label(__('users.service_group'))
                        ->placeholder('-'),

                    TextEntry::make('locale')
                        ->label(__('users.locale'))
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => match ($state) {
                            'ar'    => __('users.arabic'),
                            'en'    => __('users.english'),
                            default => $state,
                        }),

                    IconEntry::make('is_active')
                        ->label(__('users.is_active'))
                        ->boolean(),

                    TextEntry::make('last_login_at')
                        ->label(__('users.last_login_at'))
                        ->dateTime()
                        ->placeholder('-'),
                ])->columns(2),
        ]);
    }
}
