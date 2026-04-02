<!DOCTYPE html>
<html dir="{{ $isAr ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: cairo, sans-serif;
            font-size: 12px;
            color: #1E2222;
        }

        h1 {
            color: #C0392B;
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
            background: #C0392B;
            color: white;
            padding: 8px 6px;
            font-size: 11px;
        }

        td {
            padding: 6px;
            font-size: 11px;
            border-bottom: 1px solid #E8F0F0;
        }

        tr:nth-child(even) td {
            background: #FFF5F5;
        }
    </style>
</head>

<body>
    <h1>{{ $isAr ? 'المخدومين غير المزارين (+30 يوم)' : 'Unvisited Beneficiaries (+30 days)' }}</h1>
    <p class="sub">{{ now()->format('Y-m-d') }} — {{ $user->name }}</p>

    <table>
        <thead>
            <tr>
                <th>{{ $isAr ? 'الاسم' : 'Name' }}</th>
                <th>{{ $isAr ? 'الأسرة' : 'Group' }}</th>
                <th>{{ $isAr ? 'الخادم' : 'Servant' }}</th>
                <th>{{ $isAr ? 'الوضع المادي' : 'Financial' }}</th>
                <th>{{ $isAr ? 'آخر زيارة' : 'Last Visit' }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($beneficiaries as $b)
                <tr>
                    <td>{{ $b->full_name }}</td>
                    <td>{{ $b->serviceGroup?->name ?? '—' }}</td>
                    <td>{{ $b->assignedServant?->name ?? '—' }}</td>
                    <td>
                        {{ $b->financial_status
                            ? ($isAr
                                ? __("beneficiaries.{$b->financial_status}")
                                : ucfirst($b->financial_status))
                            : '—' }}
                    </td>
                    <td dir="ltr">
                        {{ $b->visits()->max('visit_date')
                            ? \Carbon\Carbon::parse($b->visits()->max('visit_date'))->format('Y-m-d')
                            : ($isAr
                                ? 'لم يُزَر'
                                : 'Never') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p style="text-align:center; margin-top:16px; color:#8F9E9E; font-size:10px;">
        {{ $isAr ? 'إجمالي:' : 'Total:' }} {{ $beneficiaries->count() }}
        @if($beneficiaries->count() >= 500)
            &nbsp;•&nbsp;{{ $isAr ? '* يعرض هذا التقرير أول 500 سجل فقط' : '* This report shows the first 500 records only' }}
        @endif
    </p>
</body>

</html>
