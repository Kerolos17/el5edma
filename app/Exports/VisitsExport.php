<?php

namespace App\Exports;

use App\Models\User;
use App\Models\Visit;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VisitsExport implements FromQuery, ShouldAutoSize, WithChunkReading, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private User $user,
        private ?string $dateFrom = null,
        private ?string $dateTo = null,
        private ?string $groupId = null,
    ) {}

    public function query()
    {
        $query = Visit::with(['beneficiary.serviceGroup', 'createdBy']);

        if ($this->user->role === 'family_leader') {
            $query->whereHas('beneficiary', fn ($q) => $q->where('service_group_id', $this->user->service_group_id),
            );
        } elseif ($this->user->role === 'servant') {
            $query->where('created_by', $this->user->id);
        }

        if ($this->groupId) {
            $query->whereHas('beneficiary', fn ($q) => $q->where('service_group_id', $this->groupId),
            );
        }

        if ($this->dateFrom) {
            $query->whereDate('visit_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('visit_date', '<=', $this->dateTo);
        }

        return $query->latest('visit_date');
    }

    public function headings(): array
    {
        $isAr = app()->getLocale() === 'ar';

        return [
            $isAr ? 'المخدوم' : 'Beneficiary',
            $isAr ? 'الأسرة' : 'Service Group',
            $isAr ? 'نوع الافتقاد' : 'Visit Type',
            $isAr ? 'تاريخ الافتقاد' : 'Visit Date',
            $isAr ? 'المدة (دقيقة)' : 'Duration (min)',
            $isAr ? 'حالة المخدوم' : 'Status',
            $isAr ? 'حرجة؟' : 'Critical?',
            $isAr ? 'الخادم' : 'Servant',
            $isAr ? 'ملاحظات' : 'Notes',
        ];
    }

    public function map($row): array
    {
        $isAr = app()->getLocale() === 'ar';

        return [
            $row->beneficiary?->full_name,
            $row->beneficiary?->serviceGroup?->name,
            $isAr ? __("visits.{$row->type}") : ucwords(str_replace('_', ' ', $row->type)),
            $row->visit_date?->format('Y-m-d H:i'),
            $row->duration_minutes ?? '—',
            $isAr ? __("visits.{$row->beneficiary_status}") : ucfirst($row->beneficiary_status),
            $row->is_critical ? ($isAr ? 'نعم' : 'Yes') : ($isAr ? 'لا' : 'No'),
            $row->createdBy?->name,
            $row->feedback ?? '—',
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
