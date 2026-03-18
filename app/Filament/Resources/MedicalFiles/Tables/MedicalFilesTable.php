<?php

namespace App\Filament\Resources\MedicalFiles\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class MedicalFilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('beneficiary.full_name')
                    ->label(__('visits.beneficiary'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->label(__('medical.file_title'))
                    ->searchable(),

                TextColumn::make('file_type')
                    ->label(__('medical.file_type'))
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'report'   => 'info',
                        'image'    => 'success',
                        'document' => 'warning',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => __("medical.{$state}")),

                TextColumn::make('uploadedBy.name')
                    ->label(__('medical.uploaded_by'))
                    ->default('—'),

                TextColumn::make('created_at')
                    ->label(__('medical.uploaded_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('file_type')
                    ->label(__('medical.file_type'))
                    ->options([
                        'report'   => __('medical.report'),
                        'image'    => __('medical.image'),
                        'document' => __('medical.document'),
                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),

                    \Filament\Actions\Action::make('download')
                        ->label(app()->getLocale() === 'ar' ? 'تحميل' : 'Download')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->url(fn ($record) => route('medical-files.download', $record))
                        ->openUrlInNewTab(),

                    DeleteAction::make()
                        ->visible(fn () => in_array(
                            Auth::user()?->role,
                            ['super_admin', 'service_leader', 'family_leader']
                        )),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}