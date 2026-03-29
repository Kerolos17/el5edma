<?php
namespace App\Filament\Resources\Visits;

use App\Filament\Resources\Visits\Pages\CreateVisit;
use App\Filament\Resources\Visits\Pages\EditVisit;
use App\Filament\Resources\Visits\Pages\ListVisits;
use App\Filament\Resources\Visits\Pages\ViewVisit;
use App\Filament\Resources\Visits\Schemas\VisitForm;
use App\Filament\Resources\Visits\Schemas\VisitInfolist;
use App\Filament\Resources\Visits\Tables\VisitsTable;
use App\Models\Visit;
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

class VisitResource extends Resource
{
    protected static ?string $model = Visit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 3;

    // العنوان الأساسي للسجل في نتائج البحث
    protected static ?string $recordTitleAttribute = 'feedback';

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.ministry');
    }

    public static function getNavigationLabel(): string
    {
        return __('visits.title');
    }

    public static function getModelLabel(): string
    {
        return __('visits.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('visits.title');
    }

    // ── Scope: كل role يشوف اللي يخصه فقط ──
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user  = Auth::user();

        // Apply role-based scoping first
        $query = match ($user?->role) {
            UserRole::FamilyLeader => $query->whereHas('beneficiary', fn($q) =>
                $q->where('service_group_id', $user->service_group_id)
            ),
            UserRole::Servant      => $query->where('created_by', $user->id),
            default                => $query,
        };

        // Apply eager loading after scoping
        return $query->with(EagerLoadingService::visitsTable());
    }

    // ── Global Search Configuration ──

    public static function getGloballySearchableAttributes(): array
    {
        // البحث في اسم المستفيد والـ Feedback الخاص بالزيارة
        return ['beneficiary.full_name', 'feedback'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('visits.type') => __("visits.{$record->type}"),
            __('visits.visit_date')         => $record->visit_date?->format('Y-m-d'),
            __('visits.beneficiary_status') => __("visits.{$record->beneficiary_status}"),
        ];
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('view', ['record' => $record]);
    }

    // ── Resource Schemas & Tables ──

    public static function form(Schema $schema): Schema
    {
        return VisitForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return VisitInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VisitsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListVisits::route('/'),
            'create' => CreateVisit::route('/create'),
            'view'   => ViewVisit::route('/{record}'),
            'edit'   => EditVisit::route('/{record}/edit'),
        ];
    }

    // ── Authorization: الخادم يستطيع الإنشاء فقط، لا يستطيع التعديل أو الحذف ──

    public static function canCreate(): bool
    {
        // الخادم يستطيع إنشاء زيارات (افتقاد)
        return true;
    }

    public static function canEdit(Model $record): bool
    {
        // فقط المسؤولين يستطيعون التعديل
        return \App\Helpers\PermissionHelper::canModify();
    }

    public static function canDelete(Model $record): bool
    {
        // فقط المسؤولين يستطيعون الحذف
        return \App\Helpers\PermissionHelper::canModify();
    }

    public static function canView(Model $record): bool
    {
        return true;
    }
}
