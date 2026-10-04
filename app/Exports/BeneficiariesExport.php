<?php

namespace App\Exports;

use App\Models\User;
use App\Support\WebAppScope;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BeneficiariesExport implements FromQuery, WithHeadings, WithMapping
{
    /**
     * $search/$filter mirror the BeneficiariesPage filters (mine / recent /
     * needs-visit) so "export" hands back exactly what the user sees.
     */
    public function __construct(
        private User $user,
        private string $search = '',
        private string $filter = 'all',
    ) {}

    public function query()
    {
        $query = WebAppScope::beneficiaries($this->user)
            ->with('serviceGroup')
            ->where('status', 'active')
            ->orderBy('full_name');

        if (trim($this->search) !== '') {
            $term = '%' . str_replace('%', '\%', trim($this->search)) . '%';
            $query->where(fn ($q) => $q
                ->where('full_name', 'like', $term)
                ->orWhere('code', 'like', $term)
                ->orWhere('phone', 'like', $term));
        }

        return match ($this->filter) {
            'mine'        => $query->where('assigned_servant_id', $this->user->id),
            'recent'      => $query->whereHas('visits', fn ($q) => $q->where('visit_date', '>=', now()->subDays(30))),
            'needs-visit' => $query->whereDoesntHave('visits'),
            default       => $query,
        };
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
