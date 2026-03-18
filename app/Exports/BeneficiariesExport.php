<?php
namespace App\Exports;

use App\Models\Beneficiary;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BeneficiariesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithChunkReading
{
    public function __construct(private User $user)
    {}

    public function query()
    {
        $query = Beneficiary::with(['serviceGroup', 'assignedServant']);

        if ($this->user->role === 'family_leader') {
            $query->where('service_group_id', $this->user->service_group_id);
        } elseif ($this->user->role === 'servant') {
            $query->where('assigned_servant_id', $this->user->id);
        }

        return $query;
    }

    public function headings(): array
    {
        $isAr = app()->getLocale() === 'ar';
        return [
            $isAr ? 'الكود' : 'Code',
            $isAr ? 'الاسم الكامل' : 'Full Name',
            $isAr ? 'تاريخ الميلاد' : 'Birth Date',
            $isAr ? 'الجنس' : 'Gender',
            $isAr ? 'الهاتف' : 'Phone',
            $isAr ? 'الحالة' : 'Status',
            $isAr ? 'الأسرة' : 'Service Group',
            $isAr ? 'الخادم' : 'Servant',
            $isAr ? 'الوضع المادي' : 'Financial Status',
            $isAr ? 'نوع الإعاقة' : 'Disability Type',
            $isAr ? 'درجة الإعاقة' : 'Disability Degree',
            $isAr ? 'المنطقة' : 'Area',
            $isAr ? 'المحافظة' : 'Governorate',
        ];
    }

    public function map($row): array
    {
        $isAr = app()->getLocale() === 'ar';
        return [
            $row->code,
            $row->full_name,
            $row->birth_date?->format('Y-m-d'),
            $isAr
                ? ($row->gender === 'male' ? 'ذكر' : 'أنثى')
                : ucfirst($row->gender),
            $row->phone,
            $isAr
                ? __("beneficiaries.{$row->status}")
                : ucfirst($row->status),
            $row->serviceGroup?->name,
            $row->assignedServant?->name,
            $row->financial_status
                ? ($isAr ? __("beneficiaries.{$row->financial_status}") : ucfirst($row->financial_status))
                : '—',
            $row->disability_type ?? '—',
            $row->disability_degree
                ? ($isAr ? __("beneficiaries.{$row->disability_degree}") : ucfirst($row->disability_degree))
                : '—',
            $row->area ?? '—',
            $row->governorate ?? '—',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF237C7C']],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
