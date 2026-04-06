<?php

namespace App\Filament\Resources\Beneficiaries\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class BeneficiaryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('tabs')->tabs([

                // ── Tab 1: البيانات الأساسية ──
                Tab::make(__('beneficiaries.tab_basic'))
                    ->icon('heroicon-o-user')
                    ->schema([
                        Section::make()
                            ->schema([
                                ImageEntry::make('photo')
                                    ->label(__('beneficiaries.photo'))
                                    ->circular()
                                    ->imageSize(150)
                                    ->checkFileExistence(false)
                                    ->getStateUsing(fn ($record) => $record->photo
                                        ? asset('storage/' . $record->photo)
                                        : 'https://ui-avatars.com/api/?name=' . urlencode($record->full_name) . '&background=2A9393&color=fff&size=150'
                                    )
                                    ->columnSpanFull(),

                                TextEntry::make('full_name')
                                    ->label(__('beneficiaries.full_name')),

                                TextEntry::make('code')
                                    ->label(__('beneficiaries.code'))
                                    ->fontFamily('mono')
                                    ->badge()
                                    ->color('gray'),

                                TextEntry::make('birth_date')
                                    ->label(__('beneficiaries.birth_date'))
                                    ->date(),

                                TextEntry::make('gender')
                                    ->label(__('beneficiaries.gender'))
                                    ->badge()
                                    ->formatStateUsing(fn (string $state) => __("beneficiaries.{$state}")),

                                TextEntry::make('status')
                                    ->label(__('beneficiaries.status'))
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'active'   => 'success',
                                        'inactive' => 'gray',
                                        'moved'    => 'info',
                                        'deceased' => 'danger',
                                        default    => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state) => __("beneficiaries.{$state}")),
                            ])->columns(2),
                    ]),

                // ── Tab 2: التواصل والأسرة ──
                Tab::make(__('beneficiaries.tab_contact'))
                    ->icon('heroicon-o-phone')
                    ->schema([
                        Section::make(__('beneficiaries.tab_contact'))
                            ->schema([
                                TextEntry::make('phone')
                                    ->label(__('beneficiaries.phone'))
                                    ->placeholder('—')
                                    ->copyable(),

                                TextEntry::make('whatsapp')
                                    ->label(__('beneficiaries.whatsapp'))
                                    ->placeholder('—'),

                                TextEntry::make('facebook_url')
                                    ->label(__('beneficiaries.facebook_url'))
                                    ->placeholder('—'),

                                TextEntry::make('instagram_url')
                                    ->label(__('beneficiaries.instagram_url'))
                                    ->placeholder('—'),
                            ])->columns(2),

                        Section::make(__('beneficiaries.guardian_section'))
                            ->schema([
                                TextEntry::make('guardian_name')
                                    ->label(__('beneficiaries.guardian_name'))
                                    ->placeholder('—'),

                                TextEntry::make('guardian_phone')
                                    ->label(__('beneficiaries.guardian_phone'))
                                    ->placeholder('—')
                                    ->copyable(),

                                TextEntry::make('guardian_relation')
                                    ->label(__('beneficiaries.guardian_relation'))
                                    ->placeholder('—'),
                            ])->columns(2),

                        Section::make(__('beneficiaries.family_section'))
                            ->description(__('beneficiaries.family_note'))
                            ->schema([
                                TextEntry::make('father_status')
                                    ->label(__('beneficiaries.father_status'))
                                    ->badge()
                                    ->color(fn ($state) => $state === 'deceased' ? 'danger' : 'success')
                                    ->formatStateUsing(fn ($state) => $state ? __("beneficiaries.{$state}") : '—'),

                                TextEntry::make('father_death_date')
                                    ->label(__('beneficiaries.father_death_date'))
                                    ->date()
                                    ->placeholder('—')
                                    ->visible(fn ($record) => $record?->father_status === 'deceased'),

                                TextEntry::make('mother_status')
                                    ->label(__('beneficiaries.mother_status'))
                                    ->badge()
                                    ->color(fn ($state) => $state === 'deceased' ? 'danger' : 'success')
                                    ->formatStateUsing(fn ($state) => $state ? __("beneficiaries.{$state}") : '—'),

                                TextEntry::make('mother_death_date')
                                    ->label(__('beneficiaries.mother_death_date'))
                                    ->date()
                                    ->placeholder('—')
                                    ->visible(fn ($record) => $record?->mother_status === 'deceased'),

                                TextEntry::make('siblings_count')
                                    ->label(__('beneficiaries.siblings_count'))
                                    ->placeholder('—'),

                                TextEntry::make('siblings_note')
                                    ->label(__('beneficiaries.siblings_note'))
                                    ->placeholder('—'),
                            ])->columns(2),

                        Section::make(__('beneficiaries.financial_section'))
                            ->schema([
                                TextEntry::make('financial_status')
                                    ->label(__('beneficiaries.financial_status'))
                                    ->badge()
                                    ->color(fn ($state): string => match ($state) {
                                        'good'      => 'success',
                                        'moderate'  => 'warning',
                                        'poor'      => 'danger',
                                        'very_poor' => 'danger',
                                        default     => 'gray',
                                    })
                                    ->formatStateUsing(fn ($state) => $state ? __("beneficiaries.{$state}") : '—'),

                                TextEntry::make('financial_notes')
                                    ->label(__('beneficiaries.financial_notes'))
                                    ->placeholder('—')
                                    ->columnSpanFull(),
                            ])->columns(2),
                    ]),

                // ── Tab 3: العنوان ──
                Tab::make(__('beneficiaries.tab_address'))
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Section::make()
                            ->schema([
                                TextEntry::make('address_text')
                                    ->label(__('beneficiaries.address_text'))
                                    ->placeholder('—')
                                    ->columnSpanFull(),

                                TextEntry::make('area')
                                    ->label(__('beneficiaries.area'))
                                    ->placeholder('—'),

                                TextEntry::make('governorate')
                                    ->label(__('beneficiaries.governorate'))
                                    ->placeholder('—'),

                                TextEntry::make('google_maps_url')
                                    ->label(__('beneficiaries.google_maps_url'))
                                    ->placeholder('—')
                                    ->url(fn ($state) => $state)
                                    ->openUrlInNewTab()
                                    ->columnSpanFull(),
                            ])->columns(2),
                    ]),

                // ── Tab 4: الحالة الطبية ──
                Tab::make(__('beneficiaries.tab_medical'))
                    ->icon('heroicon-o-heart')
                    ->schema([
                        Section::make()
                            ->schema([
                                TextEntry::make('disability_type')
                                    ->label(__('beneficiaries.disability_type'))
                                    ->placeholder('—'),

                                TextEntry::make('disability_degree')
                                    ->label(__('beneficiaries.disability_degree'))
                                    ->badge()
                                    ->color(fn ($state): string => match ($state) {
                                        'mild'     => 'success',
                                        'moderate' => 'warning',
                                        'severe'   => 'danger',
                                        default    => 'gray',
                                    })
                                    ->formatStateUsing(fn ($state) => $state ? __("beneficiaries.{$state}") : '—'),

                                TextEntry::make('doctor_name')
                                    ->label(__('beneficiaries.doctor_name'))
                                    ->placeholder('—'),

                                TextEntry::make('hospital_name')
                                    ->label(__('beneficiaries.hospital_name'))
                                    ->placeholder('—'),

                                TextEntry::make('last_medical_update')
                                    ->label(__('beneficiaries.last_medical_update'))
                                    ->date()
                                    ->placeholder('—'),

                                TextEntry::make('health_status')
                                    ->label(__('beneficiaries.health_status'))
                                    ->placeholder('—')
                                    ->columnSpanFull(),

                                TextEntry::make('medical_notes')
                                    ->label(__('beneficiaries.medical_notes'))
                                    ->placeholder('—')
                                    ->columnSpanFull(),
                            ])->columns(2),
                    ]),

                // ── Tab 5: التعيين الخدمي ──
                Tab::make(__('beneficiaries.assignment_section'))
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        Section::make()
                            ->schema([
                                TextEntry::make('serviceGroup.name')
                                    ->label(__('beneficiaries.service_group'))
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('assignedServant.name')
                                    ->label(__('beneficiaries.assigned_servant'))
                                    ->placeholder('—'),

                                TextEntry::make('created_at')
                                    ->label(__('beneficiaries.created_at'))
                                    ->dateTime(),

                                TextEntry::make('createdBy.name')
                                    ->label(__('beneficiaries.created_by'))
                                    ->placeholder('—'),
                            ])->columns(2),
                    ]),

            ])->columnSpanFull(),
        ]);
    }
}
