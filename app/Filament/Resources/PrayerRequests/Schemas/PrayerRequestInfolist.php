<?php

namespace App\Filament\Resources\PrayerRequests\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PrayerRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make(__('prayer.singular'))
                ->schema([
                    TextEntry::make('beneficiary.full_name')
                        ->label(__('visits.beneficiary')),

                    TextEntry::make('status')
                        ->label(__('prayer.status'))
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'open'     => 'warning',
                            'answered' => 'success',
                            'closed'   => 'gray',
                            default    => 'gray',
                        })
                        ->formatStateUsing(fn ($state) => __("prayer.{$state}")),

                    TextEntry::make('title')
                        ->label(__('prayer.request_title'))
                        ->columnSpanFull(),

                    TextEntry::make('body')
                        ->label(__('prayer.body'))
                        ->columnSpanFull(),

                    TextEntry::make('createdBy.name')
                        ->label(__('beneficiaries.created_by'))
                        ->placeholder('—'),

                    TextEntry::make('created_at')
                        ->label(__('beneficiaries.created_at'))
                        ->dateTime(),

                    TextEntry::make('answered_at')
                        ->label(__('prayer.answered_at'))
                        ->dateTime()
                        ->placeholder('—')
                        ->visible(fn ($record) => $record?->status === 'answered'),
                ])->columns(2),
        ]);
    }
}