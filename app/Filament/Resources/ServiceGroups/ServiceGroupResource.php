<?php

namespace App\Filament\Resources\ServiceGroups;

use App\Filament\Resources\ServiceGroups\Pages\CreateServiceGroup;
use App\Filament\Resources\ServiceGroups\Pages\EditServiceGroup;
use App\Filament\Resources\ServiceGroups\Pages\ListServiceGroups;
use App\Filament\Resources\ServiceGroups\Pages\ViewServiceGroup;
use App\Filament\Resources\ServiceGroups\Schemas\ServiceGroupForm;
use App\Filament\Resources\ServiceGroups\Schemas\ServiceGroupInfolist;
use App\Filament\Resources\ServiceGroups\Tables\ServiceGroupsTable;
use App\Models\ServiceGroup;
use App\Services\EagerLoadingService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Auth;

class ServiceGroupResource extends Resource
{
    protected static ?string $model = ServiceGroup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.ministry');
    }

    public static function getNavigationLabel(): string
    {
        return __('service_groups.title');
    }

    public static function getModelLabel(): string
    {
        return __('service_groups.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('service_groups.title');
    }

    // ── Global Search ──

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'description'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->name;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('service_groups.leader')         => $record->leader?->name        ?? '—',
            __('service_groups.service_leader') => $record->serviceLeader?->name ?? '—',
            __('service_groups.is_active')      => $record->is_active
                                                    ? __('beneficiaries.active')
                                                    : __('beneficiaries.inactive'),
        ];
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('view', ['record' => $record]);
    }

    public static function canAccess(): bool
    {
        return in_array(Auth::user()?->role, [
            UserRole::SuperAdmin, UserRole::ServiceLeader, UserRole::FamilyLeader,
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user  = Auth::user();

        // family_leader يشوف أسرته فقط
        if ($user?->role === UserRole::FamilyLeader) {
            $query->where('id', $user->service_group_id);
        }

        // Apply eager loading and aggregations
        $config = EagerLoadingService::serviceGroupsTable();
        $query->with($config['relationships'])
            ->withCount($config['withCount']);

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return ServiceGroupForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ServiceGroupInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServiceGroupsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListServiceGroups::route('/'),
            'create' => CreateServiceGroup::route('/create'),
            'view'   => ViewServiceGroup::route('/{record}'),
            'edit'   => EditServiceGroup::route('/{record}/edit'),
        ];
    }

    // ── Authorization: Using Laravel Policies for centralized authorization ──

    public static function canCreate(): bool
    {
        return Auth::user()->can('create', ServiceGroup::class);
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
