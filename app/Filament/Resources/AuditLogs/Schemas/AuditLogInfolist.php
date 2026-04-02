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
                    ->label(__('users.name')),
                TextEntry::make('model_type')
                    ->label(__('audit_logs.model')),
                TextEntry::make('model_id')
                    ->label(__('audit_logs.model_id'))
                    ->numeric(),
                TextEntry::make('action')
                    ->label(__('audit_logs.action'))
                    ->badge(),
                TextEntry::make('old_values')
                    ->label(__('audit_logs.old_values'))
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('new_values')
                    ->label(__('audit_logs.new_values'))
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('ip_address')
                    ->label(__('audit_logs.ip_address'))
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->label(__('beneficiaries.created_at'))
                    ->dateTime(),
            ]);
    }
}
