<!DOCTYPE html>
<html dir="{{ $isAr ? 'rtl' : 'ltr' }}">
<head>
<meta charset="UTF-8">
<style>
    body  { font-family: dejavusans, sans-serif; font-size: 12px; color: #1E2222; }
    h1    { color: #237C7C; font-size: 20px; text-align: center; margin-bottom: 2px; }
    h2    { color: #237C7C; font-size: 14px; border-bottom: 2px solid #237C7C; padding-bottom: 4px; margin-top: 16px; }
    .sub  { text-align: center; color: #6B7C7C; font-size: 11px; margin-bottom: 20px; }
    table.data { width: 100%; border-collapse: collapse; margin-top: 8px; }
    table.data th { background: #237C7C; color: white; padding: 7px 6px; font-size: 11px; }
    table.data td { padding: 6px; font-size: 11px; border-bottom: 1px solid #E8F0F0; }
    table.data tr:nth-child(even) td { background: #F4F8F8; }
    .warn  { color: #C0392B; font-weight: bold; }
    .ok    { color: #1A7A4A; }
    .amber { color: #B07A0D; }
</style>
</head>
<body>

    <h1>{{ $isAr ? 'مخدومو الأسرة' : 'Group Beneficiaries' }}</h1>
    <p class="sub">
        {{ $serviceGroup->name }}
        — {{ $isAr ? 'أمين الأسرة:' : 'Leader:' }} {{ $serviceGroup->leader?->name ?? '—' }}
        — {{ now()->format('Y-m-d') }}
    </p>

    <h2>
        {{ $isAr ? 'قائمة المخدومين النشطين' : 'Active Beneficiaries List' }}
        ({{ $beneficiaries->count() }})
    </h2>

    <table class="data">
        <thead>
            <tr>
                <th>#</th>
                <th>{{ $isAr ? 'الاسم' : 'Name' }}</th>
                <th>{{ $isAr ? 'الكود' : 'Code' }}</th>
                <th>{{ $isAr ? 'الهاتف' : 'Phone' }}</th>
                <th>{{ $isAr ? 'الخادم' : 'Servant' }}</th>
                <th>{{ $isAr ? 'الوضع المادي' : 'Financial' }}</th>
                <th>{{ $isAr ? 'نوع الإعاقة' : 'Disability' }}</th>
                <th>{{ $isAr ? 'آخر زيارة' : 'Last Visit' }}</th>
                <th>{{ $isAr ? 'عدد الزيارات' : 'Total Visits' }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($beneficiaries as $index => $item)
            @php
                $b         = $item['beneficiary'];
                $lastVisit = $item['last_visit'];
                $daysSince = $lastVisit
                    ? (int) now()->diffInDays(\Carbon\Carbon::parse($lastVisit))
                    : null;
                $rowClass  = match(true) {
                    is_null($daysSince)  => 'warn',
                    $daysSince > 30      => 'warn',
                    $daysSince > 14      => 'amber',
                    default              => 'ok',
                };
            @endphp
            <tr>
                <td style="text-align:center;">{{ $index + 1 }}</td>
                <td>{{ $b->full_name }}</td>
                <td>{{ $b->code }}</td>
                <td>{{ $b->phone ?? '—' }}</td>
                <td>{{ $b->assignedServant?->name ?? '—' }}</td>
                <td>
                    {{ $b->financial_status
                        ? ($isAr ? __("beneficiaries.{$b->financial_status}") : ucfirst($b->financial_status))
                        : '—' }}
                </td>
                <td>{{ $b->disability_type ?? '—' }}</td>
                <td class="{{ $rowClass }}">
                    @if($lastVisit)
                        {{ \Carbon\Carbon::parse($lastVisit)->format('Y-m-d') }}
                        ({{ $daysSince }} {{ $isAr ? 'يوم' : 'days' }})
                    @else
                        {{ $isAr ? 'لم يُزَر' : 'Never' }}
                    @endif
                </td>
                <td style="text-align:center;">{{ $item['visits_count'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ملاحظة الألوان --}}
    <p style="font-size:10px; color:#536060; margin-top:12px;">
        {{ $isAr
            ? '🔴 أحمر = لم يُزَر أو أكثر من 30 يوم | 🟡 أصفر = 14-30 يوم | 🟢 أخضر = أقل من 14 يوم'
            : '🔴 Red = Never visited or +30 days | 🟡 Yellow = 14-30 days | 🟢 Green = less than 14 days' }}
    </p>

    <p style="text-align:center; margin-top:16px; color:#8F9E9E; font-size:10px; border-top: 1px solid #E8F0F0; padding-top: 8px;">
        {{ $isAr ? 'نظام إدارة خدمة ذوي الاحتياجات الخاصة' : 'Special Needs Ministry Management System' }}
        — {{ now()->format('Y-m-d H:i') }}
    </p>

</body>
</html>
