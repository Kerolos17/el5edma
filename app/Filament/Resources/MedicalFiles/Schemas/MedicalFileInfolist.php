<?php

namespace App\Filament\Resources\MedicalFiles\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MedicalFileInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make(__('medical.files_title'))
                ->schema([
                    TextEntry::make('beneficiary.full_name')
                        ->label(__('visits.beneficiary')),

                    TextEntry::make('title')
                        ->label(__('medical.file_title')),

                    TextEntry::make('file_type')
                        ->label(__('medical.file_type'))
                        ->badge()
                        ->color(fn ($state): string => match ($state) {
                            'report'   => 'info',
                            'image'    => 'success',
                            'document' => 'warning',
                            default    => 'gray',
                        })
                        ->formatStateUsing(fn ($state) => __("medical.{$state}")),

                    TextEntry::make('uploadedBy.name')
                        ->label(__('medical.uploaded_by'))
                        ->placeholder('—'),

                    TextEntry::make('created_at')
                        ->label(__('medical.uploaded_at'))
                        ->dateTime(),

                    // زر تحميل الملف
                    TextEntry::make('file_path')
                        ->label(__('medical.upload_file'))
                        ->formatStateUsing(fn ($state) => app()->getLocale() === 'ar' ? 'تحميل الملف' : 'Download File',
                        )
                        ->url(fn ($record) => route('medical-files.download', $record),
                        )
                        ->openUrlInNewTab()
                        ->color('primary'),
                ])->columns(2),
        ]);
    }
}
