<?php
namespace App\Filament\Resources\ScheduledVisits;

use App\Filament\Resources\ScheduledVisits\Pages\CreateScheduledVisit;
use App\Filament\Resources\ScheduledVisits\Pages\EditScheduledVisit;
use App\Filament\Resources\ScheduledVisits\Pages\ListScheduledVisits;
use App\Filament\Resources\ScheduledVisits\Pages\ViewScheduledVisit;
use App\Filament\Resources\ScheduledVisits\Schemas\ScheduledVisitForm;
use App\Filament\Resources\ScheduledVisits\Schemas\ScheduledVisitInfolist;
use App\Filament\Resources\ScheduledVisits\Tables\ScheduledVisitsTable;
use App\Models\ScheduledVisit;
use App\Services\EagerLoadingService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Auth;

class ScheduledVisitResource extends Resource
{
    protected static ?string $model = ScheduledVisit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.ministry');
    }

    public static function getNavigationLabel(): string
    {
        return __('visits.scheduled_title');
    }

    public static function getModelLabel(): string
    {
        return __('visits.scheduled_title');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery()->with(EagerLoadingService::scheduledVisitsTable());
        $user  = Auth::user();

        return match ($user?->role) {
            UserRole::FamilyLeader => $query->whereHas('beneficiary', fn($q) =>
                $q->where('service_group_id', $user->service_group_id)
            ),
            UserRole::Servant      => $query->where('assigned_servant_id', $user->id),
            default                => $query,
        };
    }

    public static function form(Schema $schema): Schema
    {
        return ScheduledVisitForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ScheduledVisitInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ScheduledVisitsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListScheduledVisits::route('/'),
            'create' => CreateScheduledVisit::route('/create'),
            'view'   => ViewScheduledVisit::route('/{record}'),
            'edit'   => EditScheduledVisit::route('/{record}/edit'),
        ];
    }

    // ── Authorization: الخادم للقراءة فقط ──

    public static function canCreate(): bool
    {
        return \App\Helpers\PermissionHelper::canModify();
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
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
