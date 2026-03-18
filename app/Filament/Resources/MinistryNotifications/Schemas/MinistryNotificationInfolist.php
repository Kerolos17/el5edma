<?php

namespace App\Filament\Resources\MinistryNotifications\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MinistryNotificationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    TextEntry::make('title')
                        ->label(__('notifications.title')),

                    TextEntry::make('body')
                        ->label('')
                        ->columnSpanFull(),

                    TextEntry::make('type')
                        ->label(__('notifications.title'))
                        ->badge(),

                    TextEntry::make('created_at')
                        ->label(__('beneficiaries.created_at'))
                        ->dateTime(),
                ])->columns(2),
        ]);
    }
}
