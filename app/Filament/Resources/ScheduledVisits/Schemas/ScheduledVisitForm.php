<?php

namespace App\Filament\Resources\ScheduledVisits\Schemas;

use App\Enums\UserRole;
use App\Models\Beneficiary;
use App\Models\User;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
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
                        ->options(fn () => self::beneficiaryOptionsForActor(Auth::user()))
                        ->searchable()
                        ->required()
                        ->rules([
                            function (): Closure {
                                return function (string $attribute, mixed $value, Closure $fail): void {
                                    $beneficiary = Beneficiary::query()->find($value);

                                    if (! $beneficiary) {
                                        return;
                                    }

                                    if (! in_array((int) $beneficiary->service_group_id, self::allowedServiceGroupIdsForActor(Auth::user()), true)) {
                                        $fail(__('users.unauthorized_role'));
                                    }
                                };
                            },
                        ])
                        ->live(),

                    Select::make('assigned_servant_id')
                        ->label(__('beneficiaries.assigned_servant'))
                        ->options(fn ($get) => self::servantOptionsForBeneficiary(Auth::user(), $get('beneficiary_id')))
                        ->default(fn () => Auth::user()->role === UserRole::Servant ? Auth::id() : null)
                        ->searchable()
                        ->required()
                        ->rules([
                            function (callable $get): Closure {
                                return function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                    if (! $value) {
                                        return;
                                    }

                                    $beneficiary = Beneficiary::query()->find($get('beneficiary_id'));
                                    $servant     = User::query()
                                        ->whereKey($value)
                                        ->where('role', UserRole::Servant)
                                        ->where('is_active', true)
                                        ->first();

                                    if (! $beneficiary || ! $servant || (int) $servant->service_group_id !== (int) $beneficiary->service_group_id) {
                                        $fail(__('users.unauthorized_role'));
                                    }
                                };
                            },
                        ]),

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

    private static function allowedServiceGroupIdsForActor(?User $actor): array
    {
        if (! $actor) {
            return [];
        }

        return match ($actor->role) {
            UserRole::SuperAdmin => Beneficiary::query()
                ->where('status', 'active')
                ->distinct()
                ->pluck('service_group_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->all(),
            UserRole::ServiceLeader => $actor->managedServiceGroupIds(),
            UserRole::FamilyLeader, UserRole::Servant => $actor->service_group_id ? [(int) $actor->service_group_id] : [],
            default => [],
        };
    }

    private static function beneficiaryOptionsForActor(?User $actor): array
    {
        $allowedGroupIds = self::allowedServiceGroupIdsForActor($actor);

        if (empty($allowedGroupIds)) {
            return [];
        }

        return Beneficiary::query()
            ->where('status', 'active')
            ->whereIn('service_group_id', $allowedGroupIds)
            ->pluck('full_name', 'id')
            ->toArray();
    }

    private static function servantOptionsForBeneficiary(?User $actor, mixed $beneficiaryId): array
    {
        $beneficiary = Beneficiary::query()->find($beneficiaryId);

        if (! $beneficiary || ! in_array((int) $beneficiary->service_group_id, self::allowedServiceGroupIdsForActor($actor), true)) {
            return [];
        }

        return User::query()
            ->where('role', UserRole::Servant)
            ->where('is_active', true)
            ->where('service_group_id', $beneficiary->service_group_id)
            ->pluck('name', 'id')
            ->toArray();
    }
}
