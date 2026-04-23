<!DOCTYPE html>
<html dir="{{ $isAr ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: dejavusans, sans-serif;
            font-size: 12px;
            color: #1E2222;
        }

        h1 {
            color: #237C7C;
            font-size: 18px;
            text-align: center;
            margin-bottom: 4px;
        }

        p.sub {
            text-align: center;
            color: #6B7C7C;
            font-size: 11px;
            margin-bottom: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #237C7C;
            color: white;
            padding: 8px 6px;
            font-size: 11px;
        }

        td {
            padding: 6px;
            font-size: 11px;
            border-bottom: 1px solid #E8F0F0;
            vertical-align: top;
        }

        tr:nth-child(even) td {
            background: #F4F8F8;
        }

        .critical {
            color: #C0392B;
            font-weight: bold;
        }

        .muted {
            color: #6B7C7C;
        }
    </style>
</head>

<body>
    <h1>{{ $isAr ? 'تقرير الزيارات' : 'Visits Report' }}</h1>
    <p class="sub">
        {{ $isAr ? 'تاريخ الإنشاء:' : 'Generated:' }} {{ now()->format('Y-m-d H:i') }}
        - {{ $user->name }}
        @if ($dateFrom || $dateTo)
            <br>
            {{ $isAr ? 'الفترة:' : 'Date Range:' }}
            {{ $dateFrom ?: ($isAr ? 'من البداية' : 'From start') }}
            {{ $isAr ? 'إلى' : 'to' }}
            {{ $dateTo ?: ($isAr ? 'الآن' : 'now') }}
        @endif
    </p>

    <table>
        <thead>
            <tr>
                <th>{{ $isAr ? 'المخدوم' : 'Beneficiary' }}</th>
                <th>{{ $isAr ? 'الأسرة' : 'Group' }}</th>
                <th>{{ $isAr ? 'نوع الزيارة' : 'Visit Type' }}</th>
                <th>{{ $isAr ? 'التاريخ' : 'Visit Date' }}</th>
                <th>{{ $isAr ? 'الخادم' : 'Servant' }}</th>
                <th>{{ $isAr ? 'الحالة' : 'Status' }}</th>
                <th>{{ $isAr ? 'حرجة' : 'Critical' }}</th>
                <th>{{ $isAr ? 'ملاحظات' : 'Notes' }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($visits as $visit)
                <tr>
                    <td>{{ $visit->beneficiary?->full_name ?? '—' }}</td>
                    <td>{{ $visit->beneficiary?->serviceGroup?->name ?? '—' }}</td>
                    <td>{{ $isAr ? __("visits.{$visit->type}") : ucwords(str_replace('_', ' ', $visit->type)) }}</td>
                    <td dir="ltr">{{ $visit->visit_date?->format('Y-m-d H:i') ?? '—' }}</td>
                    <td>{{ $visit->createdBy?->name ?? '—' }}</td>
                    <td>{{ $isAr ? __("visits.{$visit->beneficiary_status}") : ucfirst($visit->beneficiary_status) }}</td>
                    <td class="{{ $visit->is_critical ? 'critical' : 'muted' }}">
                        {{ $visit->is_critical ? ($isAr ? 'نعم' : 'Yes') : ($isAr ? 'لا' : 'No') }}
                    </td>
                    <td>{{ $visit->feedback ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; color: #6B7C7C; padding: 16px;">
                        {{ $isAr ? 'لا توجد زيارات مطابقة للفلاتر المحددة.' : 'No visits match the selected filters.' }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p style="text-align:center; margin-top:16px; color:#8F9E9E; font-size:10px;">
        {{ $isAr ? 'إجمالي:' : 'Total:' }} {{ $visits->count() }}
        @if ($visits->count() >= 500)
            &nbsp;•&nbsp;{{ $isAr ? '* يعرض هذا التقرير أول 500 سجل فقط' : '* This report shows the first 500 records only' }}
        @endif
    </p>
</body>

</html>
