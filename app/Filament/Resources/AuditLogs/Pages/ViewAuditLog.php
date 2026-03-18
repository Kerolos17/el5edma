<?php

namespace App\Filament\Resources\AuditLogs\Pages;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewAuditLog extends ViewRecord
{
    protected static string $resource = AuditLogResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    TextEntry::make('user.name')
                        ->label(__('users.name')),

                    TextEntry::make('action')
                        ->label('Action')
                        ->badge()
                        ->color(fn ($state) => match ($state) {
                            'created' => 'success',
                            'updated' => 'warning',
                            'deleted' => 'danger',
                            default   => 'gray',
                        }),

                    TextEntry::make('model_type')
                        ->label('Model')
                        ->formatStateUsing(fn ($state) => class_basename($state)),

                    TextEntry::make('model_id')
                        ->label('ID')
                        ->fontFamily('mono'),

                    TextEntry::make('ip_address')
                        ->label('IP')
                        ->fontFamily('mono')
                        ->placeholder('—'),

                    TextEntry::make('created_at')
                        ->label(__('beneficiaries.created_at'))
                        ->dateTime(),
                ])->columns(2),

            Section::make(app()->getLocale() === 'ar' ? 'القيم القديمة' : 'Old Values')
                ->schema([
                    KeyValueEntry::make('old_values')
                        ->label('')
                        ->columnSpanFull(),
                ])
                ->visible(fn ($record) => ! empty($record->old_values)),

            Section::make(app()->getLocale() === 'ar' ? 'القيم الجديدة' : 'New Values')
                ->schema([
                    KeyValueEntry::make('new_values')
                        ->label('')
                        ->columnSpanFull(),
                ])
                ->visible(fn ($record) => ! empty($record->new_values)),
        ]);
    }
}
