<?php

namespace App\Filament\Resources\MedicalFiles\Schemas;

use App\Enums\UserRole;
use App\Models\Beneficiary;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class MedicalFileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make(__('medical.files_title'))
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
                        ->label(__('medical.file_title'))
                        ->required()
                        ->maxLength(255),

                    Select::make('file_type')
                        ->label(__('medical.file_type'))
                        ->options([
                            'report'   => __('medical.report'),
                            'image'    => __('medical.image'),
                            'document' => __('medical.document'),
                        ])
                        ->required(),

                    FileUpload::make('file_path')
                        ->label(__('medical.upload_file'))
                        ->disk('private')
                        ->directory(fn ($get) => 'medical/' . ($get('beneficiary_id') ?? 'general'),
                        )
                        ->acceptedFileTypes([
                            'application/pdf',
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        ])
                        ->maxSize(5120) // 5MB
                        ->required()
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }
}
