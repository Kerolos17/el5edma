<?php

namespace App\Filament\Resources\AuditLogs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AuditLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')
                    ->label('User'),
                TextEntry::make('model_type'),
                TextEntry::make('model_id')
                    ->numeric(),
                TextEntry::make('action')
                    ->badge(),
                TextEntry::make('old_values')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('new_values')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('ip_address')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime(),
            ]);
    }
}
