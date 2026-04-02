<?php

namespace App\Filament\Resources\PrayerRequests\Schemas;

use App\Models\Beneficiary;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Auth;

class PrayerRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make(__('prayer.singular'))
                ->schema([
                    Select::make('beneficiary_id')
                        ->label(__('visits.beneficiary'))
                        ->options(function () {
                            $user  = Auth::user();
                            $query = Beneficiary::where('status', 'active');

                            if ($user->role === UserRole::FamilyLeader) {
                                $query->where('service_group_id', $user->service_group_id);
                            } elseif ($user->role === UserRole::Servant) {
                                $query->where('assigned_servant_id', $user->id);
                            }

                            return $query->pluck('full_name', 'id');
                        })
                        ->searchable()
                        ->required(),

                    TextInput::make('title')
                        ->label(__('prayer.request_title'))
                        ->required()
                        ->maxLength(255),

                    Select::make('status')
                        ->label(__('prayer.status'))
                        ->options([
                            'open'     => __('prayer.open'),
                            'answered' => __('prayer.answered'),
                            'closed'   => __('prayer.closed'),
                        ])
                        ->default('open')
                        ->required(),

                    Textarea::make('body')
                        ->label(__('prayer.body'))
                        ->rows(5)
                        ->required()
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }
}
