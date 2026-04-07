<?php

namespace App\Filament\Resources\ScheduledVisits\Schemas;

use App\Models\Beneficiary;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Auth;

class ScheduledVisitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make(__('visits.scheduled_title'))
                ->schema([
                    Select::make('beneficiary_id')
                        ->label(__('visits.beneficiary'))
                        ->options(function () {
                            $user  = Auth::user();
                            $query = Beneficiary::where('status', 'active');

                            if ($user->role === UserRole::FamilyLeader) {
                                $query->where('service_group_id', $user->service_group_id);
                            } elseif ($user->role === UserRole::Servant) {
                                $query->where('service_group_id', $user->service_group_id);
                            }

                            return $query->pluck('full_name', 'id');
                        })
                        ->searchable()
                        ->required(),

                    Select::make('assigned_servant_id')
                        ->label(__('beneficiaries.assigned_servant'))
                        ->options(
                            User::where('role', UserRole::Servant)
                                ->where('is_active', true)
                                ->pluck('name', 'id'),
                        )
                        ->default(fn () => Auth::user()->role === UserRole::Servant ? Auth::id() : null)
                        ->searchable()
                        ->required(),

                    DatePicker::make('scheduled_date')
                        ->label(__('visits.scheduled_date'))
                        ->required()
                        ->minDate(today()),

                    TimePicker::make('scheduled_time')
                        ->label(__('visits.scheduled_time'))
                        ->required(),

                    Select::make('status')
                        ->label(__('beneficiaries.status'))
                        ->options([
                            'pending'   => __('visits.pending'),
                            'completed' => __('visits.completed'),
                            'cancelled' => __('visits.cancelled'),
                        ])
                        ->default('pending')
                        ->required(),

                    Textarea::make('notes')
                        ->label(__('visits.feedback'))
                        ->rows(3)
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }
}
