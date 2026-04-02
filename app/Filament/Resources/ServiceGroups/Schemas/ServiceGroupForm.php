<?php

namespace App\Filament\Resources\ServiceGroups\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use App\Enums\UserRole;
use Filament\Schemas\Schema;

class ServiceGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make(__('service_groups.singular'))
                ->schema([
                    TextInput::make('name')
                        ->label(__('service_groups.name'))
                        ->required()
                        ->maxLength(255),

                    Textarea::make('description')
                        ->label(__('service_groups.description'))
                        ->rows(3)
                        ->maxLength(1000)
                        ->columnSpanFull(),

                    Toggle::make('is_active')
                        ->label(__('service_groups.is_active'))
                        ->default(true),
                ])->columns(2),

            Section::make(__('navigation.management'))
                ->schema([
                    Select::make('leader_id')
                        ->label(__('service_groups.leader'))
                        ->options(
                            User::where('role', UserRole::FamilyLeader)
                                ->where('is_active', true)
                                ->pluck('name', 'id'),
                        )
                        ->searchable()
                        ->nullable(),

                    Select::make('service_leader_id')
                        ->label(__('service_groups.service_leader'))
                        ->options(
                            User::where('role', UserRole::ServiceLeader)
                                ->where('is_active', true)
                                ->pluck('name', 'id'),
                        )
                        ->searchable()
                        ->nullable(),
                ])->columns(2),
        ]);
    }
}
