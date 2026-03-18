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
            vertical-align: middle;
        }

        tr:nth-child(even) td {
            background: #F4F8F8;
        }

        .badge-active {
            color: #1A7A4A;
            font-weight: bold;
        }

        .badge-inactive {
            color: #6B7C7C;
        }

        .badge-critical {
            color: #C0392B;
            font-weight: bold;
        }

        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            display: block;
            margin: auto;
        }

        .avatar-placeholder {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #D0E8E8;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: auto;
            font-size: 14px;
            color: #237C7C;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <h1>{{ $isAr ? 'تقرير المخدومين' : 'Beneficiaries Report' }}</h1>
    <p class="sub">
        {{ $isAr ? 'تاريخ الإنشاء:' : 'Generated:' }}
        {{ now()->format('Y-m-d H:i') }}
        — {{ $user->name }}
    </p>

    <table>
        <thead>
            <tr>
                <th>{{ $isAr ? 'الكود' : 'Code' }}</th>
                <th>{{ $isAr ? 'الاسم' : 'Name' }}</th>
                <th>{{ $isAr ? 'الهاتف' : 'Phone' }}</th>
                <th>{{ $isAr ? 'الأسرة' : 'Group' }}</th>
                <th>{{ $isAr ? 'الخادم' : 'Servant' }}</th>
                <th>{{ $isAr ? 'الحالة' : 'Status' }}</th>
                <th>{{ $isAr ? 'الوضع المادي' : 'Financial' }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($beneficiaries as $b)
                <tr>
                    <td dir="ltr">{{ $b->code }}</td>
                    <td>{{ $b->full_name }}</td>
                    <td dir="ltr">{{ $b->phone ?? '—' }}</td>
                    <td>{{ $b->serviceGroup?->name ?? '—' }}</td>
                    <td>{{ $b->assignedServant?->name ?? '—' }}</td>
                    <td class="badge-{{ $b->status }}">
                        {{ $isAr ? __("beneficiaries.{$b->status}") : ucfirst($b->status) }}
                    </td>
                    <td>
                        {{ $b->financial_status
                            ? ($isAr
                                ? __("beneficiaries.{$b->financial_status}")
                                : ucfirst($b->financial_status))
                            : '—' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p style="text-align:center; margin-top:16px; color:#8F9E9E; font-size:10px;">
        {{ $isAr ? 'إجمالي:' : 'Total:' }} {{ $beneficiaries->count() }}
    </p>
</body>

</html>
