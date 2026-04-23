<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Models\ServiceGroup;
use App\Models\User;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make(__('users.title'))
                ->schema([
                    FileUpload::make('profile_photo')
                        ->label(__('users.profile_photo'))
                        ->image()
                        ->disk('public')
                        ->directory('users/photos')
                        ->imageEditor()
                        ->circleCropper()
                        ->acceptedFileTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'])
                        ->maxSize(1024)
                        ->helperText(__('users.profile_photo_helper'))
                        ->columnSpanFull(),

                    TextInput::make('name')
                        ->label(__('users.name'))
                        ->required()
                        ->maxLength(255),

                    TextInput::make('email')
                        ->label(__('users.email'))
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),

                    TextInput::make('phone')
                        ->label(__('users.phone'))
                        ->tel()
                        ->maxLength(20),

                    TextInput::make('password')
                        ->label(__('users.password'))
                        ->password()
                        ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                        ->dehydrated(fn ($state) => filled($state))
                        ->required(fn (string $operation) => $operation === 'create')
                        ->maxLength(255),
                ])->columns(2),

            Section::make(__('users.role'))
                ->schema([
                    Select::make('role')
                        ->label(__('users.role'))
                        ->options(fn () => self::roleOptionsForActor(Auth::user()))
                        ->visible(fn (?User $record) => self::canManageRoleFields(Auth::user(), $record))
                        ->dehydrated(fn (?User $record) => self::canManageRoleFields(Auth::user(), $record))
                        ->required(fn (?User $record) => self::canManageRoleFields(Auth::user(), $record))
                        ->rules([
                            function (): Closure {
                                return function (string $attribute, mixed $value, Closure $fail): void {
                                    $actor  = Auth::user();
                                    $record = request()->route('record');

                                    if ($record instanceof User
                                        && $record->id === $actor->id
                                        && $value      === $actor->role->value) {
                                        return;
                                    }

                                    $allowed = self::allowedRoleValuesForActor($actor);

                                    if (! in_array($value, $allowed, true)) {
                                        $fail(__('users.unauthorized_role'));
                                    }
                                };
                            },
                        ])
                        ->live()
                        ->afterStateUpdated(fn ($state, callable $set) => in_array($state, [UserRole::SuperAdmin->value, UserRole::ServiceLeader->value])
                                ? $set('service_group_id', null)
                                : null,
                        ),

                    Select::make('service_group_id')
                        ->label(__('users.service_group'))
                        ->options(fn () => self::serviceGroupOptionsForActor(Auth::user()))
                        ->searchable()
                        ->nullable()
                        ->dehydrated(fn ($get, ?User $record) => self::canManageRoleFields(Auth::user(), $record)
                            && in_array($get('role'), [UserRole::FamilyLeader->value, UserRole::Servant->value], true),
                        )
                        ->required(fn ($get, ?User $record) => self::canManageRoleFields(Auth::user(), $record)
                            && in_array($get('role'), [UserRole::FamilyLeader->value, UserRole::Servant->value], true),
                        )
                        ->visible(fn ($get, ?User $record) => self::canManageRoleFields(Auth::user(), $record)
                            && in_array($get('role'), [UserRole::FamilyLeader->value, UserRole::Servant->value], true),
                        )
                        ->rules([
                            function (callable $get): Closure {
                                return function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                    $actor  = Auth::user();
                                    $record = request()->route('record');

                                    if ($record instanceof User && $record->id === $actor->id) {
                                        return;
                                    }

                                    $role = $get('role');

                                    if (! in_array($role, [UserRole::FamilyLeader->value, UserRole::Servant->value], true)) {
                                        return;
                                    }

                                    $allowedGroupIds = self::allowedServiceGroupIdsForActor($actor);

                                    if (! in_array((int) $value, $allowedGroupIds, true)) {
                                        $fail(__('users.unauthorized_role'));
                                    }
                                };
                            },
                        ]),

                    Select::make('locale')
                        ->label(__('users.locale'))
                        ->options([
                            'ar' => __('users.arabic'),
                            'en' => __('users.english'),
                        ])
                        ->default('ar')
                        ->required(),

                    Toggle::make('is_active')
                        ->label(__('users.is_active'))
                        ->default(true)
                        ->visible(fn (?User $record) => self::canManageRoleFields(Auth::user(), $record)),
                ])->columns(2),

            Section::make(__('users.personal_code'))
                ->schema([
                    TextInput::make('personal_code')
                        ->label(__('users.personal_code'))
                        ->helperText(__('users.code_hint'))
                        ->readOnly()
                        ->disabled()
                        ->dehydrated(false)
                        ->maxLength(10)
                        ->placeholder(__('users.code_auto_generated')),
                ])
                ->visible(fn () => Auth::check() && Auth::user()->role === UserRole::SuperAdmin)]);
    }

    private static function canManageRoleFields(User $actor, ?User $record): bool
    {
        return $actor->role === UserRole::SuperAdmin
            || ($actor->role === UserRole::ServiceLeader && $record?->id !== $actor->id);
    }

    private static function allowedRoleValuesForActor(User $actor): array
    {
        return match ($actor->role) {
            UserRole::SuperAdmin    => array_column(UserRole::cases(), 'value'),
            UserRole::ServiceLeader => [UserRole::FamilyLeader->value, UserRole::Servant->value],
            default                 => [],
        };
    }

    private static function roleOptionsForActor(User $actor): array
    {
        return collect(self::allowedRoleValuesForActor($actor))
            ->mapWithKeys(fn (string $role) => [$role => UserRole::from($role)->label()])
            ->toArray();
    }

    private static function allowedServiceGroupIdsForActor(User $actor): array
    {
        return match ($actor->role) {
            UserRole::SuperAdmin => ServiceGroup::query()
                ->where('is_active', true)
                ->pluck('id')
                ->all(),
            UserRole::ServiceLeader => ServiceGroup::query()
                ->where('is_active', true)
                ->where('service_leader_id', $actor->id)
                ->pluck('id')
                ->all(),
            default => [],
        };
    }

    private static function serviceGroupOptionsForActor(User $actor): array
    {
        return ServiceGroup::query()
            ->whereIn('id', self::allowedServiceGroupIdsForActor($actor))
            ->pluck('name', 'id')
            ->toArray();
    }
}
