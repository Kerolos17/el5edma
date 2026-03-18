<?php

namespace App\Filament\Resources\ServiceGroups\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ServiceGroupInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make(__('service_groups.singular'))
                ->schema([
                    TextEntry::make('name')
                        ->label(__('service_groups.name')),

                    TextEntry::make('description')
                        ->label(__('service_groups.description'))
                        ->placeholder('-')
                        ->columnSpanFull(),

                    IconEntry::make('is_active')
                        ->label(__('service_groups.is_active'))
                        ->boolean(),
                ])->columns(2),

            Section::make(__('navigation.management'))
                ->schema([
                    TextEntry::make('leader.name')
                        ->label(__('service_groups.leader'))
                        ->placeholder('-'),

                    TextEntry::make('serviceLeader.name')
                        ->label(__('service_groups.service_leader'))
                        ->placeholder('-'),

                    TextEntry::make('servants_count')
                        ->label(__('service_groups.servants_count'))
                        ->state(fn ($record) => $record->servants()->count())
                        ->badge()
                        ->color('info'),

                    TextEntry::make('beneficiaries_count')
                        ->label(__('service_groups.beneficiaries_count'))
                        ->state(fn ($record) => $record->beneficiaries()->count())
                        ->badge()
                        ->color('success'),
                ])->columns(2),
        ]);
    }
}
