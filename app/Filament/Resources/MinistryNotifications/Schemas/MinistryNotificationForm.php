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
                    ->label(__('users.singular'))
                    ->required(),
                Select::make('type')
                    ->label(__('notifications.type'))
                    ->options([
                        'birthday'           => __('notifications.types.birthday'),
                        'critical_case'      => __('notifications.types.critical_case'),
                        'visit_reminder'     => __('notifications.types.visit_reminder'),
                        'unvisited_alert'    => __('notifications.types.unvisited_alert'),
                        'new_beneficiary'    => __('notifications.types.new_beneficiary'),
                        'servant_registered' => __('notifications.types.servant_registered'),
                    ])
                    ->required(),
                TextInput::make('title')
                    ->label(__('notifications.title_field'))
                    ->required(),
                Textarea::make('body')
                    ->label(__('notifications.body_field'))
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('data')
                    ->label(__('notifications.data_field'))
                    ->default(null)
                    ->columnSpanFull()
                    ->helperText(__('notifications.data_helper')),
                DateTimePicker::make('read_at')
                    ->label(__('notifications.read_at')),
            ]);
    }
}
