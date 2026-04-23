<?php

namespace App\Filament\Resources\Visits\Schemas;

use App\Enums\UserRole;
use App\Models\Beneficiary;
use App\Models\User;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class VisitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make(__('visits.singular'))
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
                        ->required()
                        ->rules([
                            function (): Closure {
                                return function (string $attribute, mixed $value, Closure $fail): void {
                                    $actor       = Auth::user();
                                    $beneficiary = Beneficiary::query()->find($value);

                                    if (! $beneficiary) {
                                        return;
                                    }

                                    if (in_array($actor->role, [UserRole::FamilyLeader, UserRole::Servant], true)
                                        && $beneficiary->service_group_id !== $actor->service_group_id) {
                                        $fail(__('visits.beneficiary') . ' ' . __('users.unauthorized_role'));
                                    }
                                };
                            },
                        ])
                        ->live(),

                    Select::make('type')
                        ->label(__('visits.type'))
                        ->options([
                            'home_visit'     => __('visits.home_visit'),
                            'phone_call'     => __('visits.phone_call'),
                            'church_meeting' => __('visits.church_meeting'),
                        ])
                        ->required(),

                    DateTimePicker::make('visit_date')
                        ->label(__('visits.visit_date'))
                        ->required()
                        ->default(now()),

                    TextInput::make('duration_minutes')
                        ->label(__('visits.duration_minutes'))
                        ->numeric()
                        ->minValue(1)
                        ->nullable(),
                ])->columns(2),

            Section::make(__('visits.beneficiary_status'))
                ->schema([
                    Select::make('beneficiary_status')
                        ->label(__('visits.beneficiary_status'))
                        ->options([
                            'great'        => __('visits.great'),
                            'good'         => __('visits.good'),
                            'needs_follow' => __('visits.needs_follow'),
                            'critical'     => __('visits.critical'),
                        ])
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state === 'critical') {
                                $set('is_critical', true);
                                $set('needs_family_leader', true);
                                $set('needs_service_leader', true);
                            } else {
                                $set('is_critical', false);
                            }
                        }),

                    Select::make('servants')
                        ->label(__('visits.servants'))
                        ->multiple()
                        ->relationship('servants', 'name')
                        ->options(function ($get) {
                            $beneficiaryId = $get('beneficiary_id');

                            $query = User::where('role', UserRole::Servant)->where('is_active', true);

                            if ($beneficiaryId) {
                                $query->whereIn(
                                    'service_group_id',
                                    Beneficiary::where('id', $beneficiaryId)->whereNotNull('service_group_id')->pluck('service_group_id'),
                                );
                            }

                            return $query->pluck('name', 'id');
                        })
                        ->searchable(),

                    Textarea::make('feedback')
                        ->label(__('visits.feedback'))
                        ->rows(4)
                        ->columnSpanFull(),
                ])->columns(2),

            Section::make(__('visits.is_critical'))
                ->schema([
                    Toggle::make('is_critical')
                        ->label(__('visits.is_critical'))
                        ->live()
                        ->helperText(fn ($get) => $get('is_critical')
                                ? __('visits.critical_alert')
                                : null,
                        ),

                    Toggle::make('needs_family_leader')
                        ->label(__('visits.needs_family_leader')),

                    Toggle::make('needs_service_leader')
                        ->label(__('visits.needs_service_leader')),
                ])->columns(3),
        ]);
    }
}
