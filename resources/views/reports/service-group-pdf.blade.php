<!DOCTYPE html>
<html dir="{{ $isAr ? 'rtl' : 'ltr' }}">
<head>
<meta charset="UTF-8">
<style>
    body  { font-family: dejavusans, sans-serif; font-size: 12px; color: #1E2222; }
    h1    { color: #237C7C; font-size: 20px; text-align: center; margin-bottom: 2px; }
    h2    { color: #237C7C; font-size: 14px; border-bottom: 2px solid #237C7C; padding-bottom: 4px; margin-top: 20px; }
    .sub  { text-align: center; color: #6B7C7C; font-size: 11px; margin-bottom: 20px; }
    .grid { width: 100%; }
    .grid td { width: 50%; vertical-align: top; padding: 4px 8px; }
    .label { font-size: 11px; color: #6B7C7C; font-weight: bold; }
    .value { font-size: 12px; color: #1E2222; }
    table.data { width: 100%; border-collapse: collapse; margin-top: 8px; }
    table.data th { background: #237C7C; color: white; padding: 7px 6px; font-size: 11px; }
    table.data td { padding: 6px; font-size: 11px; border-bottom: 1px solid #E8F0F0; }
    table.data tr:nth-child(even) td { background: #F4F8F8; }
    .stat-box { display: inline-block; background: #EFF8F8; border: 1px solid #B3DEDE; border-radius: 6px; padding: 8px 16px; margin: 4px; text-align: center; }
    .stat-num  { font-size: 22px; font-weight: bold; color: #237C7C; }
    .stat-lbl  { font-size: 10px; color: #536060; }
</style>
</head>
<body>

    <h1>{{ $isAr ? 'تقرير الأسرة الخدمية' : 'Service Group Report' }}</h1>
    <p class="sub">{{ $serviceGroup->name }} — {{ now()->format('Y-m-d') }}</p>

    {{-- ── بيانات الأسرة ── --}}
    <h2>{{ $isAr ? 'بيانات الأسرة' : 'Group Information' }}</h2>
    <table class="grid">
        <tr>
            <td>
                <div class="label">{{ $isAr ? 'اسم الأسرة' : 'Group Name' }}</div>
                <div class="value">{{ $serviceGroup->name }}</div>
            </td>
            <td>
                <div class="label">{{ $isAr ? 'الوصف' : 'Description' }}</div>
                <div class="value">{{ $serviceGroup->description ?? '—' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">{{ $isAr ? 'أمين الأسرة' : 'Group Leader' }}</div>
                <div class="value">{{ $serviceGroup->leader?->name ?? '—' }}</div>
            </td>
            <td>
                <div class="label">{{ $isAr ? 'أمين الخدمة' : 'Service Leader' }}</div>
                <div class="value">{{ $serviceGroup->serviceLeader?->name ?? '—' }}</div>
            </td>
        </tr>
    </table>

    {{-- ── إحصائيات سريعة ── --}}
    <h2>{{ $isAr ? 'إحصائيات الشهر الحالي' : 'Current Month Statistics' }}</h2>
    <div style="text-align:center; margin: 12px 0;">
        <div class="stat-box">
            <div class="stat-num">{{ $serviceGroup->servants->count() }}</div>
            <div class="stat-lbl">{{ $isAr ? 'خادم' : 'Servants' }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-num">{{ $serviceGroup->beneficiaries->count() }}</div>
            <div class="stat-lbl">{{ $isAr ? 'مخدوم نشط' : 'Active Beneficiaries' }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-num">{{ $servantStats->sum('visits_this_month') }}</div>
            <div class="stat-lbl">{{ $isAr ? 'زيارة هذا الشهر' : 'Visits This Month' }}</div>
        </div>
    </div>

    {{-- ── الخدام وأدائهم ── --}}
    <h2>{{ $isAr ? 'الخدام وأدائهم' : 'Servants & Performance' }}</h2>
    <table class="data">
        <thead>
            <tr>
                <th>{{ $isAr ? 'اسم الخادم' : 'Servant Name' }}</th>
                <th>{{ $isAr ? 'البريد' : 'Email' }}</th>
                <th>{{ $isAr ? 'عدد المخدومين' : 'Beneficiaries' }}</th>
                <th>{{ $isAr ? 'زيارات هذا الشهر' : 'Visits This Month' }}</th>
                <th>{{ $isAr ? 'آخر زيارة' : 'Last Visit' }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($servantStats as $stat)
            <tr>
                <td>{{ $stat['servant']->name }}</td>
                <td>{{ $stat['servant']->email }}</td>
                <td style="text-align:center;">{{ $stat['assigned_count'] }}</td>
                <td style="text-align:center; font-weight:bold; color:#237C7C;">
                    {{ $stat['visits_this_month'] }}
                </td>
                <td>
                    {{ $stat['last_visit']
                        ? \Carbon\Carbon::parse($stat['last_visit'])->format('Y-m-d')
                        : ($isAr ? 'لا توجد زيارات' : 'No visits') }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p style="text-align:center; margin-top:20px; color:#8F9E9E; font-size:10px; border-top: 1px solid #E8F0F0; padding-top: 8px;">
        {{ $isAr ? 'نظام إدارة خدمة ذوي الاحتياجات الخاصة' : 'Special Needs Ministry Management System' }}
        — {{ now()->format('Y-m-d H:i') }}
    </p>

</body>
</html>
