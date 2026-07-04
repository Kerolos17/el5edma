<?php

namespace App\Exports;

use App\Models\User;
use App\Support\WebAppScope;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BeneficiariesExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private User $user) {}

    public function query()
    {
        return WebAppScope::beneficiaries($this->user)
            ->with('serviceGroup')
            ->where('status', 'active')
            ->orderBy('full_name');
    }

    public function headings(): array
    {
        return ['الاسم', 'الكود', 'رقم الهاتف', 'مجموعة الخدمة', 'تاريخ الميلاد', 'الحالة'];
    }

    public function map($beneficiary): array
    {
        return [
            $beneficiary->full_name,
            $beneficiary->code,
            $beneficiary->phone,
            $beneficiary->serviceGroup?->name,
            $beneficiary->birth_date?->toDateString(),
            $beneficiary->status,
        ];
    }
}
