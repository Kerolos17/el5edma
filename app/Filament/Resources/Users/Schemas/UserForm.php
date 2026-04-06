<?php
namespace App\Filament\Resources\Users\Schemas;

use App\Models\ServiceGroup;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use App\Enums\UserRole;
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
                        ->dehydrateStateUsing(fn($state) => Hash::make($state))
                        ->dehydrated(fn($state) => filled($state))
                        ->required(fn(string $operation) => $operation === 'create')
                        ->maxLength(255),
                ])->columns(2),

            Section::make(__('users.role'))
                ->schema([
                    Select::make('role')
                        ->label(__('users.role'))
                        ->options(UserRole::options())
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn ($state, callable $set) => in_array($state, [UserRole::SuperAdmin->value, UserRole::ServiceLeader->value])
                                ? $set('service_group_id', null)
                                : null,
                        ),

                    Select::make('service_group_id')
                        ->label(__('users.service_group'))
                        ->options(
                            ServiceGroup::where('is_active', true)->pluck('name', 'id'),
                        )
                        ->searchable()
                        ->nullable()
                        ->visible(fn ($get) => in_array($get('role'), [UserRole::FamilyLeader->value, UserRole::Servant->value])),

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
                        ->default(true),
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
}
