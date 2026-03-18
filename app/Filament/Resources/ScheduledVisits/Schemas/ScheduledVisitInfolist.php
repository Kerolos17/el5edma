<?php

namespace App\Filament\Resources\ScheduledVisits\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ScheduledVisitInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make(__('visits.scheduled_title'))
                ->schema([
                    TextEntry::make('beneficiary.full_name')
                        ->label(__('visits.beneficiary')),

                    TextEntry::make('assignedServant.name')
                        ->label(__('beneficiaries.assigned_servant')),

                    TextEntry::make('scheduled_date')
                        ->label(__('visits.scheduled_date'))
                        ->date(),

                    TextEntry::make('scheduled_time')
                        ->label(__('visits.scheduled_time')),

                    TextEntry::make('status')
                        ->label(__('beneficiaries.status'))
                        ->badge()
                        ->color(fn ($state): string => match ($state) {
                            'pending'   => 'warning',
                            'completed' => 'success',
                            'cancelled' => 'danger',
                            default     => 'gray',
                        })
                        ->formatStateUsing(fn ($state) => __("visits.{$state}")),

                    TextEntry::make('reminder_sent_at')
                        ->label(__('visits.reminder_sent'))
                        ->dateTime()
                        ->placeholder('—'),

                    TextEntry::make('notes')
                        ->label(__('visits.feedback'))
                        ->placeholder('—')
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }
}
