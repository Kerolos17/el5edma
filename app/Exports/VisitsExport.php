<?php

namespace App\Exports;

use App\Models\User;
use App\Support\WebAppScope;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class VisitsExport implements FromQuery, WithHeadings, WithMapping
{
    /**
     * $search/$filter mirror the VisitsPage filters (month / critical /
     * follow-up) so "export" hands back exactly what the user sees.
     */
    public function __construct(
        private User $user,
        private string $search = '',
        private string $filter = 'all',
    ) {}

    public function query()
    {
        $query = WebAppScope::visits($this->user)
            ->with('beneficiary')
            ->orderBy('visit_date', 'desc');

        if (trim($this->search) !== '') {
            $term = '%' . str_replace('%', '\%', trim($this->search)) . '%';
            $query->where(fn ($q) => $q
                ->where('feedback', 'like', $term)
                ->orWhere('type', 'like', $term)
                ->orWhereHas('beneficiary', fn ($b) => $b->where('full_name', 'like', $term)));
        }

        return match ($this->filter) {
            'month'     => $query->whereMonth('visit_date', now()->month)->whereYear('visit_date', now()->year),
            'critical'  => $query->where('is_critical', true)->whereNull('critical_resolved_at'),
            'follow-up' => $query->where(fn ($q) => $q->where('needs_family_leader', true)->orWhere('needs_service_leader', true)),
            default     => $query,
        };
    }

    public function headings(): array
    {
        return ['المخدوم', 'تاريخ الزيارة', 'النوع', 'نوع الخدمة', 'الحالة', 'ملاحظات'];
    }

    public function map($visit): array
    {
        return [
            $visit->beneficiary?->full_name,
            $visit->visit_date?->toDateString(),
            $visit->type,
            $visit->service_type,
            $visit->status,
            $visit->notes,
        ];
    }
}
