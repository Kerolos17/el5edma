<?php
namespace App\Filament\Resources\Beneficiaries;

use App\Filament\Resources\Beneficiaries\Pages\CreateBeneficiary;
use App\Filament\Resources\Beneficiaries\Pages\EditBeneficiary;
use App\Filament\Resources\Beneficiaries\Pages\ListBeneficiaries;
use App\Filament\Resources\Beneficiaries\Pages\ViewBeneficiary;
use App\Filament\Resources\Beneficiaries\Schemas\BeneficiaryForm;
use App\Filament\Resources\Beneficiaries\Schemas\BeneficiaryInfolist;
use App\Filament\Resources\Beneficiaries\Tables\BeneficiariesTable;
use App\Models\Beneficiary;
use App\Services\EagerLoadingService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BeneficiaryResource extends Resource
{
    protected static ?string $model = Beneficiary::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static ?int $navigationSort = 1;

    // العنوان الأساسي للسجل في نتائج البحث
    protected static ?string $recordTitleAttribute = 'full_name';

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.ministry');
    }

    public static function getNavigationLabel(): string
    {
        return __('beneficiaries.title');
    }

    public static function getModelLabel(): string
    {
        return __('beneficiaries.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('beneficiaries.title');
    }

    // ── Scope: كل دور (Role) يرى البيانات المخصصة له فقط ──
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user  = Auth::user();

        // Apply role-based scoping BEFORE eager loading
        $query = match ($user?->role) {
            'family_leader', 'servant' => $query->where('service_group_id', $user->service_group_id),
            default => $query,
        };

        // Apply eager loading for table relationships
        $query->with(EagerLoadingService::beneficiariesTable())
            ->withMax('visits', 'visit_date');

        return $query;
    }

    // ── Global Search Configuration ──

    /**
     * عرض صورة المخدوم في نتائج البحث العالمي
     */
    public static function getGlobalSearchResultImage(Model $record): ?string
    {
        if (! $record->photo) {
            return 'https://ui-avatars.com/api/?name=' . urlencode($record->full_name) . '&background=2A9393&color=fff';
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return $disk->url($record->photo);
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->full_name;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'full_name',
            'code',
            'phone',
            'whatsapp',
            'guardian_name',
            'guardian_phone',
            'area',
            'governorate',
        ];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('beneficiaries.code')             => $record->code,
            __('beneficiaries.service_group')    => $record->serviceGroup?->name ?? '—',
            __('beneficiaries.assigned_servant') => $record->assignedServant?->name ?? '—',
            __('beneficiaries.status')           => __("beneficiaries.{$record->status}"),
        ];
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('view', ['record' => $record]);
    }

    // ── Resource Schemas & Tables ──

    public static function form(Schema $schema): Schema
    {
        return BeneficiaryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BeneficiaryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BeneficiariesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListBeneficiaries::route('/'),
            'create' => CreateBeneficiary::route('/create'),
            'view'   => ViewBeneficiary::route('/{record}'),
            'edit'   => EditBeneficiary::route('/{record}/edit'),
        ];
    }

    // ── Authorization: من يستطيع الإنشاء والتعديل ──

    /**
     * فقط أمين الخدمة ومدير النظام وأمين الأسرة يستطيعون إنشاء مخدومين جدد
     * الخادم (servant) لا يستطيع الإنشاء
     */
    public static function canCreate(): bool
    {
        return \App\Helpers\PermissionHelper::canModify();
    }

    /**
     * فقط أمين الخدمة ومدير النظام وأمين الأسرة يستطيعون تعديل المخدومين
     * الخادم (servant) لا يستطيع التعديل
     */
    public static function canEdit(Model $record): bool
    {
        return \App\Helpers\PermissionHelper::canModify();
    }

    /**
     * فقط أمين الخدمة ومدير النظام وأمين الأسرة يستطيعون حذف المخدومين
     */
    public static function canDelete(Model $record): bool
    {
        return \App\Helpers\PermissionHelper::canModify();
    }

    /**
     * الجميع يستطيع عرض المخدومين (حسب الصلاحيات في getEloquentQuery)
     */
    public static function canView(Model $record): bool
    {
        return true;
    }
}
