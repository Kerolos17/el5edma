<?php

namespace App\Filament\Resources\MinistryNotifications\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MinistryNotificationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('type')
                    ->options([
                        'birthday'        => 'Birthday',
                        'critical_case'   => 'Critical case',
                        'visit_reminder'  => 'Visit reminder',
                        'unvisited_alert' => 'Unvisited alert',
                        'new_beneficiary' => 'New beneficiary',
                    ])
                    ->required(),
                TextInput::make('title')
                    ->required(),
                Textarea::make('body')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('data')
                    ->default(null)
                    ->columnSpanFull(),
                DateTimePicker::make('read_at'),
            ]);
    }
}
