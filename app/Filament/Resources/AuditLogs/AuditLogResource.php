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
    return __('navigation.audit_log');
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
    return app()->getLocale() === 'ar'
        ? 'لا توجد سجلات تعديل'
        : 'No audit logs found';
}

    // فقط super_admin و service_leader
    public static function canAccess(): bool
    {
        return in_array(Auth::user()?->role, [UserRole::SuperAdmin, UserRole::ServiceLeader]);
    }

    // لا يسمح بالإنشاء أو التعديل أو الحذف — قراءة فقط
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }

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
