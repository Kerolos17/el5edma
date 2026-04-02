<?php

namespace App\Filament\Resources\MinistryNotifications;

use App\Filament\Resources\MinistryNotifications\Pages\ListMinistryNotifications;
use App\Filament\Resources\MinistryNotifications\Tables\MinistryNotificationsTable;
use App\Models\MinistryNotification;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class MinistryNotificationResource extends Resource
{
    protected static ?string $model = MinistryNotification::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.main');
    }

    public static function getNavigationLabel(): string
    {
        return __('notifications.title');
    }

    public static function getModelLabel(): string
    {
        return __('notifications.title');
    }

    // كل user يشوف إشعاراته فقط
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', Auth::id())
            ->latest('created_at');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return MinistryNotificationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMinistryNotifications::route('/'),
        ];
    }

    // ── Authorization: Using Laravel Policies for centralized authorization ──

    public static function canAccess(): bool
    {
        return Auth::user()->can('viewAny', MinistryNotification::class);
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('create', MinistryNotification::class);
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->can('update', $record);
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->can('delete', $record);
    }

    public static function canView(Model $record): bool
    {
        return Auth::user()->can('view', $record);
    }
}
