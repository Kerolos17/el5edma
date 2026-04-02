<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\AuditLogs\Pages\ViewAuditLog;
use App\Filament\Resources\AuditLogs\Tables\AuditLogsTable;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Auth;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.management');
    }

    public static function getNavigationLabel(): string
    {
        return in_array(Auth::user()?->role, [UserRole::SuperAdmin, UserRole::ServiceLeader]);
    }

    public static function getModelLabel(): string
    {
        return __('navigation.audit_log');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.audit_logs');
    }

    // Empty state text
    public static function getEmptyStateHeading(): string
    {
        return __('audit_logs.no_records');
    }

    // ── Authorization: Using Laravel Policies for centralized authorization ──

    public static function canAccess(): bool
    {
        return Auth::user()->can('viewAny', AuditLog::class);
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('create', AuditLog::class);
    }

    public static function canEdit($record): bool
    {
        return Auth::user()->can('update', $record);
    }

    public static function canDelete($record): bool
    {
        return Auth::user()->can('delete', $record);
    }

    public static function canView(Model $record): bool
    {
        return Auth::user()->can('view', $record);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return AuditLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
            'view'  => ViewAuditLog::route('/{record}'),
        ];
    }
}
