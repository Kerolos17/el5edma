<?php

namespace App\Filament\Resources\Beneficiaries\Schemas;

use App\Enums\UserRole;
use App\Models\ServiceGroup;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class BeneficiaryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('tabs')->tabs([

                // ── Tab 1: البيانات الأساسية ──
                Tab::make('basic')
                    ->key('basic')
                    ->label(null)
                    ->icon('heroicon-o-user')
                    ->schema([
                        Section::make()
                            ->schema([
                                FileUpload::make('photo')
                                    ->label(__('beneficiaries.photo'))
                                    ->image()
                                    ->disk('public')
                                    ->directory('beneficiaries/photos')
                                    ->imageEditor()
                                    ->imageEditorAspectRatioOptions([
                                        '1:1',
                                        '4:3',
                                        '16:9',
                                    ])
                                    ->imageAspectRatio('1:1')
                                    ->automaticallyCropImagesToAspectRatio()
                                    ->automaticallyResizeImagesToWidth(800)
                                    ->automaticallyResizeImagesToHeight(800)
                                    ->acceptedFileTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'])
                                    ->maxSize(5120)
                                    ->helperText(__('beneficiaries.photo_helper'))
                                    ->columnSpanFull(),

                                TextInput::make('full_name')
                                    ->label(__('beneficiaries.full_name'))
                                    ->required()
                                    ->maxLength(255)
                                    ->rules(['required', 'string', 'max:255']),

                                DatePicker::make('birth_date')
                                    ->label(__('beneficiaries.birth_date'))
                                    ->required()
                                    ->maxDate(now())
                                    ->rules(['required', 'date', 'before_or_equal:today']),

                                Select::make('gender')
                                    ->label(__('beneficiaries.gender'))
                                    ->options([
                                        'male'   => __('beneficiaries.male'),
                                        'female' => __('beneficiaries.female'),
                                    ])
                                    ->required()
                                    ->rules(['required', 'in:male,female']),

                                Select::make('status')
                                    ->label(__('beneficiaries.status'))
                                    ->options([
                                        'active'   => __('beneficiaries.active'),
                                        'inactive' => __('beneficiaries.inactive'),
                                        'moved'    => __('beneficiaries.moved'),
                                        'deceased' => __('beneficiaries.deceased'),
                                    ])
                                    ->default('active')
                                    ->required()
                                    ->rules(['required', 'in:active,inactive,moved,deceased']),
                            ])->columns(['default' => 1, 'sm' => 2]),
                    ]),

                // ── Tab 2: التواصل والأسرة ──
                Tab::make('contact')
                    ->key('contact')
                    ->label(null)
                    ->icon('heroicon-o-phone')
                    ->schema([

                        Section::make(__('beneficiaries.contact_section'))
                            ->schema([
                                TextInput::make('phone')
                                    ->label(__('beneficiaries.phone'))
                                    ->tel()
                                    ->maxLength(20)
                                    ->rules([
                                        'nullable',
                                        'string',
                                        'max:20',
                                        'regex:/^(\+20|0020|20)?[1-9][0-9]{8,9}$/',
                                    ])
                                    ->validationMessages([
                                        'regex' => __('validation.egyptian_phone'),
                                    ]),

                                TextInput::make('whatsapp')
                                    ->label(__('beneficiaries.whatsapp'))
                                    ->tel()
                                    ->maxLength(20)
                                    ->rules([
                                        'nullable',
                                        'string',
                                        'max:20',
                                        'regex:/^(\+20|0020|20)?[1-9][0-9]{8,9}$/',
                                    ])
                                    ->validationMessages([
                                        'regex' => __('validation.egyptian_phone'),
                                    ]),

                                TextInput::make('facebook_url')
                                    ->label(__('beneficiaries.facebook_url'))
                                    ->url()
                                    ->maxLength(255)
                                    ->rules(['nullable', 'url', 'max:255']),

                                TextInput::make('instagram_url')
                                    ->label(__('beneficiaries.instagram_url'))
                                    ->url()
                                    ->maxLength(255)
                                    ->rules(['nullable', 'url', 'max:255']),
                            ])->columns(['default' => 1, 'sm' => 2]),

                        Section::make(__('beneficiaries.guardian_section'))
                            ->schema([
                                TextInput::make('guardian_name')
                                    ->label(__('beneficiaries.guardian_name'))
                                    ->maxLength(255)
                                    ->rules(['nullable', 'string', 'max:255']),

                                TextInput::make('guardian_phone')
                                    ->label(__('beneficiaries.guardian_phone'))
                                    ->tel()
                                    ->maxLength(20)
                                    ->rules([
                                        'nullable',
                                        'string',
                                        'max:20',
                                        'regex:/^(\+20|0020|20)?[1-9][0-9]{8,9}$/',
                                    ])
                                    ->validationMessages([
                                        'regex' => __('validation.egyptian_phone'),
                                    ]),

                                TextInput::make('guardian_relation')
                                    ->label(__('beneficiaries.guardian_relation'))
                                    ->maxLength(50)
                                    ->rules(['nullable', 'string', 'max:50']),
                            ])->columns(['default' => 1, 'sm' => 2]),

                        Section::make(__('beneficiaries.family_section'))
                            ->description(__('beneficiaries.family_note'))
                            ->schema([
                                Select::make('father_status')
                                    ->label(__('beneficiaries.father_status'))
                                    ->options([
                                        'alive'    => __('beneficiaries.alive'),
                                        'deceased' => __('beneficiaries.deceased'),
                                        'unknown'  => __('beneficiaries.unknown'),
                                    ])
                                    ->live()
                                    ->nullable(),

                                DatePicker::make('father_death_date')
                                    ->label(__('beneficiaries.father_death_date'))
                                    ->visible(fn ($get) => $get('father_status') === 'deceased')
                                    ->nullable(),

                                Select::make('mother_status')
                                    ->label(__('beneficiaries.mother_status'))
                                    ->options([
                                        'alive'    => __('beneficiaries.alive'),
                                        'deceased' => __('beneficiaries.deceased'),
                                        'unknown'  => __('beneficiaries.unknown'),
                                    ])
                                    ->live()
                                    ->nullable(),

                                DatePicker::make('mother_death_date')
                                    ->label(__('beneficiaries.mother_death_date'))
                                    ->visible(fn ($get) => $get('mother_status') === 'deceased')
                                    ->nullable(),

                                TextInput::make('siblings_count')
                                    ->label(__('beneficiaries.siblings_count'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->nullable()
                                    ->rules(['nullable', 'integer', 'min:0', 'max:30']),

                                TextInput::make('siblings_note')
                                    ->label(__('beneficiaries.siblings_note'))
                                    ->maxLength(255)
                                    ->rules(['nullable', 'string', 'max:255']),
                            ])->columns(['default' => 1, 'sm' => 2]),

                        Section::make(__('beneficiaries.financial_section'))
                            ->schema([
                                Select::make('financial_status')
                                    ->label(__('beneficiaries.financial_status'))
                                    ->options([
                                        'good'      => __('beneficiaries.good'),
                                        'moderate'  => __('beneficiaries.moderate'),
                                        'poor'      => __('beneficiaries.poor'),
                                        'very_poor' => __('beneficiaries.very_poor'),
                                    ])
                                    ->nullable(),

                                Textarea::make('financial_notes')
                                    ->label(__('beneficiaries.financial_notes'))
                                    ->rows(2)
                                    ->maxLength(1000)
                                    ->rules(['nullable', 'string', 'max:1000'])
                                    ->columnSpanFull(),
                            ])->columns(['default' => 1, 'sm' => 2]),
                    ]),

                // ── Tab 3: العنوان ──
                Tab::make('address')
                    ->key('address')
                    ->label(null)
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Section::make()
                            ->schema([
                                Textarea::make('address_text')
                                    ->label(__('beneficiaries.address_text'))
                                    ->rows(3)
                                    ->maxLength(1000)
                                    ->rules(['nullable', 'string', 'max:1000'])
                                    ->columnSpanFull(),

                                TextInput::make('area')
                                    ->label(__('beneficiaries.area'))
                                    ->maxLength(100)
                                    ->rules(['nullable', 'string', 'max:100']),

                                TextInput::make('governorate')
                                    ->label(__('beneficiaries.governorate'))
                                    ->maxLength(100)
                                    ->rules(['nullable', 'string', 'max:100']),

                                TextInput::make('google_maps_url')
                                    ->label(__('beneficiaries.google_maps_url'))
                                    ->url()
                                    ->maxLength(500)
                                    ->columnSpanFull()
                                    ->rules([
                                        'nullable',
                                        'url',
                                        'max:500',
                                        'regex:/^https:\/\/(maps\.google\.com|www\.google\.com\/maps|goo\.gl\/maps)(\/.*)?(\?.*)?$/',
                                    ])
                                    ->validationMessages([
                                        'regex' => __('validation.google_maps_url'),
                                    ]),
                            ])->columns(['default' => 1, 'sm' => 2]),
                    ]),

                // ── Tab 4: الحالة الطبية ──
                Tab::make('medical')
                    ->key('medical')
                    ->label(null)
                    ->icon('heroicon-o-heart')
                    ->schema([
                        Section::make()
                            ->schema([
                                TextInput::make('disability_type')
                                    ->label(__('beneficiaries.disability_type'))
                                    ->maxLength(100)
                                    ->rules(['nullable', 'string', 'max:100']),

                                Select::make('disability_degree')
                                    ->label(__('beneficiaries.disability_degree'))
                                    ->options([
                                        'mild'     => __('beneficiaries.mild'),
                                        'moderate' => __('beneficiaries.moderate'),
                                        'severe'   => __('beneficiaries.severe'),
                                    ])
                                    ->rules(['nullable', 'in:mild,moderate,severe'])
                                    ->nullable(),

                                TextInput::make('doctor_name')
                                    ->label(__('beneficiaries.doctor_name'))
                                    ->maxLength(100)
                                    ->rules(['nullable', 'string', 'max:100']),

                                TextInput::make('hospital_name')
                                    ->label(__('beneficiaries.hospital_name'))
                                    ->maxLength(100)
                                    ->rules(['nullable', 'string', 'max:100']),

                                DatePicker::make('last_medical_update')
                                    ->label(__('beneficiaries.last_medical_update'))
                                    ->maxDate(now())
                                    ->rules(['nullable', 'date', 'before_or_equal:today'])
                                    ->nullable(),

                                Textarea::make('health_status')
                                    ->label(__('beneficiaries.health_status'))
                                    ->rows(3)
                                    ->maxLength(1000)
                                    ->rules(['nullable', 'string', 'max:1000'])
                                    ->columnSpanFull(),

                                Textarea::make('medical_notes')
                                    ->label(__('beneficiaries.medical_notes'))
                                    ->rows(3)
                                    ->maxLength(2000)
                                    ->rules(['nullable', 'string', 'max:2000'])
                                    ->columnSpanFull(),
                            ])->columns(['default' => 1, 'sm' => 2]),
                    ]),

                // ── Tab 5: التعيين الخدمي ──
                Tab::make('assignment')
                    ->key('assignment')
                    ->label(null)
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        Section::make()
                            ->schema([
                                Select::make('service_group_id')
                                    ->label(__('beneficiaries.service_group'))
                                    ->options(
                                        ServiceGroup::where('is_active', true)
                                            ->pluck('name', 'id'),
                                    )
                                    ->searchable()
                                    ->required()
                                    ->live(),

                                Select::make('assigned_servant_id')
                                    ->label(__('beneficiaries.assigned_servant'))
                                    ->options(function ($get) {
                                        $groupId = $get('service_group_id');
                                        if (! $groupId) {
                                            return User::where('role', UserRole::Servant)
                                                ->where('is_active', true)
                                                ->pluck('name', 'id');
                                        }

                                        return User::where('role', UserRole::Servant)
                                            ->where('is_active', true)
                                            ->where('service_group_id', $groupId)
                                            ->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->nullable(),
                            ])->columns(['default' => 1, 'sm' => 2]),
                    ]),

            ])->columnSpanFull(),
        ]);
    }
}
