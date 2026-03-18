<?php

namespace App\Filament\Resources\Visits\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VisitInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make(__('visits.singular'))
                ->schema([
                    TextEntry::make('beneficiary.full_name')
                        ->label(__('visits.beneficiary')),

                    TextEntry::make('type')
                        ->label(__('visits.type'))
                        ->badge()
                        ->color('info')
                        ->formatStateUsing(fn ($state) => __("visits.{$state}")),

                    TextEntry::make('visit_date')
                        ->label(__('visits.visit_date'))
                        ->dateTime(),

                    TextEntry::make('duration_minutes')
                        ->label(__('visits.duration_minutes'))
                        ->placeholder('—'),

                    TextEntry::make('beneficiary_status')
                        ->label(__('visits.beneficiary_status'))
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'great'        => 'success',
                            'good'         => 'info',
                            'needs_follow' => 'warning',
                            'critical'     => 'danger',
                            default        => 'gray',
                        })
                        ->formatStateUsing(fn ($state) => __("visits.{$state}")),

                    TextEntry::make('createdBy.name')
                        ->label(__('beneficiaries.created_by'))
                        ->placeholder('—'),
                ])->columns(2),

            Section::make(__('visits.feedback'))
                ->schema([
                    TextEntry::make('feedback')
                        ->label(__('visits.feedback'))
                        ->placeholder('—')
                        ->columnSpanFull(),
                ]),

            Section::make(__('visits.is_critical'))
                ->schema([
                    IconEntry::make('is_critical')
                        ->label(__('visits.is_critical'))
                        ->boolean(),

                    IconEntry::make('needs_family_leader')
                        ->label(__('visits.needs_family_leader'))
                        ->boolean(),

                    IconEntry::make('needs_service_leader')
                        ->label(__('visits.needs_service_leader'))
                        ->boolean(),

                    TextEntry::make('critical_resolved_at')
                        ->label(__('visits.resolved_at'))
                        ->dateTime()
                        ->placeholder('—'),

                    TextEntry::make('resolvedBy.name')
                        ->label(__('visits.resolved_by'))
                        ->placeholder('—'),
                ])->columns(3),
        ]);
    }
}
