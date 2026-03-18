<?php

namespace App\Filament\Resources\MedicalFiles;

use App\Filament\Resources\MedicalFiles\Pages\CreateMedicalFile;
use App\Filament\Resources\MedicalFiles\Pages\ListMedicalFiles;
use App\Filament\Resources\MedicalFiles\Pages\ViewMedicalFile;
use App\Filament\Resources\MedicalFiles\Schemas\MedicalFileForm;
use App\Filament\Resources\MedicalFiles\Schemas\MedicalFileInfolist;
use App\Filament\Resources\MedicalFiles\Tables\MedicalFilesTable;
use App\Models\MedicalFile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class MedicalFileResource extends Resource
{
    protected static ?string $model = MedicalFile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static ?int $navigationSort = 6;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.ministry');
    }

    public static function getNavigationLabel(): string
    {
        return __('medical.files_title');
    }

    public static function getModelLabel(): string
    {
        return __('medical.files_title');
    }

    public static function getPluralModelLabel(): string
    {
        return __('medical.files_title');
    }

    // لا يسمح بالتعديل — immutable
    public static function canEdit($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery()->with(['beneficiary', 'uploadedBy']);
        $user  = Auth::user();

        return match ($user?->role) {
            'family_leader' => $query->whereHas('beneficiary', fn ($q) =>
                $q->where('service_group_id', $user->service_group_id)
            ),
            'servant' => $query->whereHas('beneficiary', fn ($q) =>
                $q->where('assigned_servant_id', $user->id)
            ),
            default => $query,
        };
    }

    public static function form(Schema $schema): Schema
    {
        return MedicalFileForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MedicalFileInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MedicalFilesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListMedicalFiles::route('/'),
            'create' => CreateMedicalFile::route('/create'),
            'view'   => ViewMedicalFile::route('/{record}'),
        ];
    }

    // ── Authorization: الخادم للقراءة فقط ──

    public static function canCreate(): bool
    {
        return \App\Helpers\PermissionHelper::canModify();
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return \App\Helpers\PermissionHelper::canModify();
    }

    public static function canView(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return true;
    }
}