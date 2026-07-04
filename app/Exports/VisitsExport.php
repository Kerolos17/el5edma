<?php

namespace App\Exports;

use App\Models\User;
use App\Support\WebAppScope;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class VisitsExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private User $user) {}

    public function query()
    {
        return WebAppScope::visits($this->user)
            ->with('beneficiary')
            ->orderBy('visit_date', 'desc');
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
