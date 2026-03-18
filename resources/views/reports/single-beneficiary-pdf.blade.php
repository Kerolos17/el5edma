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
            font-size: 20px;
            text-align: center;
            margin-bottom: 2px;
        }

        h2 {
            color: #237C7C;
            font-size: 14px;
            border-bottom: 2px solid #237C7C;
            padding-bottom: 4px;
            margin-top: 20px;
        }

        h3 {
            color: #1D6666;
            font-size: 13px;
            margin-bottom: 6px;
        }

        .sub {
            text-align: center;
            color: #6B7C7C;
            font-size: 11px;
            margin-bottom: 20px;
        }

        .grid {
            width: 100%;
        }

        .grid td {
            width: 50%;
            vertical-align: top;
            padding: 4px 8px;
        }

        .label {
            font-size: 11px;
            color: #6B7C7C;
            font-weight: bold;
        }

        .value {
            font-size: 12px;
            color: #1E2222;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        table.data th {
            background: #237C7C;
            color: white;
            padding: 7px 6px;
            font-size: 11px;
        }

        table.data td {
            padding: 6px;
            font-size: 11px;
            border-bottom: 1px solid #E8F0F0;
        }

        table.data tr:nth-child(even) td {
            background: #F4F8F8;
        }

        .badge-active {
            color: #1A7A4A;
            font-weight: bold;
        }

        .badge-critical {
            color: #C0392B;
            font-weight: bold;
        }

        .badge-needs_follow {
            color: #B07A0D;
            font-weight: bold;
        }

        .note-box {
            background: #FDF3DC;
            border-right: 3px solid #CF9210;
            padding: 8px 12px;
            font-size: 11px;
            margin: 8px 0;
            border-radius: 4px;
        }

        .separator {
            border: none;
            border-top: 1px dashed #D4DEDE;
            margin: 12px 0;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .profile-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #237C7C;
            display: block;
            margin: 0 auto 10px;
        }

        .profile-placeholder {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: #D0E8E8;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 40px;
            color: #237C7C;
            font-weight: bold;
            border: 3px solid #237C7C;
        }
    </style>
</head>

<body>

    <h1>{{ $isAr ? 'ملف المخدوم الكامل' : 'Full Beneficiary Profile' }}</h1>
    <p class="sub">
        {{ $isAr ? 'تاريخ التقرير:' : 'Report Date:' }} {{ now()->format('Y-m-d') }}
    </p>

    {{-- ── صورة المخدوم ── --}}
    <div class="profile-header">
        @if ($beneficiary->photo)
            @php
                $imagePath = storage_path('app/public/' . $beneficiary->photo);
                $imageData = null;
                $imageType = 'png';

                if (file_exists($imagePath)) {
                    $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));

                    // Convert WebP to PNG for mPDF compatibility
                    if ($extension === 'webp' && function_exists('imagecreatefromwebp')) {
                        $image = imagecreatefromwebp($imagePath);
                        if ($image) {
                            ob_start();
                            imagepng($image, null, 9);
                            $imageData = base64_encode(ob_get_clean());
                            imagedestroy($image);
                            $imageType = 'png';
                        }
                    } else {
                        // For other formats, use as-is
                        $imageData = base64_encode(file_get_contents($imagePath));
                        $imageType = in_array($extension, ['jpg', 'jpeg', 'png', 'gif']) ? $extension : 'png';
                    }
                }
            @endphp
            @if ($imageData)
                <img src="data:image/{{ $imageType }};base64,{{ $imageData }}" class="profile-photo"
                    alt="{{ $beneficiary->full_name }}">
            @else
                <div class="profile-placeholder">{{ mb_substr($beneficiary->full_name, 0, 1) }}</div>
            @endif
        @else
            <div class="profile-placeholder">{{ mb_substr($beneficiary->full_name, 0, 1) }}</div>
        @endif
    </div>

    {{-- ── البيانات الأساسية ── --}}
    <h2>{{ $isAr ? 'البيانات الأساسية' : 'Basic Information' }}</h2>
    <table class="grid">
        <tr>
            <td>
                <div class="label">{{ $isAr ? 'الاسم الكامل' : 'Full Name' }}</div>
                <div class="value">{{ $beneficiary->full_name }}</div>
            </td>
            <td>
                <div class="label">{{ $isAr ? 'الكود' : 'Code' }}</div>
                <div class="value">{{ $beneficiary->code }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">{{ $isAr ? 'تاريخ الميلاد' : 'Birth Date' }}</div>
                <div class="value">
                    {{ $beneficiary->birth_date?->format('Y-m-d') }}
                    ({{ $isAr ? 'العمر:' : 'Age:' }} {{ $beneficiary->birth_date?->age }})
                </div>
            </td>
            <td>
                <div class="label">{{ $isAr ? 'الجنس' : 'Gender' }}</div>
                <div class="value">
                    {{ $isAr ? ($beneficiary->gender === 'male' ? 'ذكر' : 'أنثى') : ucfirst($beneficiary->gender) }}
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">{{ $isAr ? 'الحالة' : 'Status' }}</div>
                <div class="value badge-{{ $beneficiary->status }}">
                    {{ $isAr ? __("beneficiaries.{$beneficiary->status}") : ucfirst($beneficiary->status) }}
                </div>
            </td>
            <td>
                <div class="label">{{ $isAr ? 'الأسرة الخدمية' : 'Service Group' }}</div>
                <div class="value">{{ $beneficiary->serviceGroup?->name ?? '—' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">{{ $isAr ? 'الخادم المسؤول' : 'Assigned Servant' }}</div>
                <div class="value">{{ $beneficiary->assignedServant?->name ?? '—' }}</div>
            </td>
            <td>
                <div class="label">{{ $isAr ? 'أضافه' : 'Added By' }}</div>
                <div class="value">{{ $beneficiary->createdBy?->name ?? '—' }}</div>
            </td>
        </tr>
    </table>

    {{-- ── التواصل ── --}}
    <h2>{{ $isAr ? 'بيانات التواصل' : 'Contact Information' }}</h2>
    <table class="grid">
        <tr>
            <td>
                <div class="label">{{ $isAr ? 'هاتف' : 'Phone' }}</div>
                <div class="value">{{ $beneficiary->phone ?? '—' }}</div>
            </td>
            <td>
                <div class="label">{{ $isAr ? 'واتساب' : 'WhatsApp' }}</div>
                <div class="value">{{ $beneficiary->whatsapp ?? '—' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">{{ $isAr ? 'اسم ولي الأمر' : 'Guardian Name' }}</div>
                <div class="value">{{ $beneficiary->guardian_name ?? '—' }}</div>
            </td>
            <td>
                <div class="label">{{ $isAr ? 'هاتف ولي الأمر' : 'Guardian Phone' }}</div>
                <div class="value">{{ $beneficiary->guardian_phone ?? '—' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">{{ $isAr ? 'العنوان' : 'Address' }}</div>
                <div class="value">{{ $beneficiary->address_text ?? '—' }}</div>
            </td>
            <td>
                <div class="label">{{ $isAr ? 'المنطقة / المحافظة' : 'Area / Governorate' }}</div>
                <div class="value">{{ $beneficiary->area ?? '—' }} / {{ $beneficiary->governorate ?? '—' }}</div>
            </td>
        </tr>
    </table>

    {{-- ── الوضع الأسري ── --}}
    <h2>{{ $isAr ? 'الوضع الأسري والمادي' : 'Family & Financial Situation' }}</h2>
    <div class="note-box">
        {{ $isAr
            ? 'هذه بيانات العائلة الحقيقية للمخدوم، وليست الأسرة الخدمية'
            : "This is the beneficiary's biological family information, not the ministry service group." }}
    </div>
    <table class="grid">
        <tr>
            <td>
                <div class="label">{{ $isAr ? 'حالة الأب' : 'Father Status' }}</div>
                <div class="value">
                    {{ $beneficiary->father_status
                        ? ($isAr
                            ? __("beneficiaries.{$beneficiary->father_status}")
                            : ucfirst($beneficiary->father_status))
                        : '—' }}
                    @if ($beneficiary->father_status === 'deceased' && $beneficiary->father_death_date)
                        ({{ $beneficiary->father_death_date->format('Y-m-d') }})
                    @endif
                </div>
            </td>
            <td>
                <div class="label">{{ $isAr ? 'حالة الأم' : 'Mother Status' }}</div>
                <div class="value">
                    {{ $beneficiary->mother_status
                        ? ($isAr
                            ? __("beneficiaries.{$beneficiary->mother_status}")
                            : ucfirst($beneficiary->mother_status))
                        : '—' }}
                    @if ($beneficiary->mother_status === 'deceased' && $beneficiary->mother_death_date)
                        ({{ $beneficiary->mother_death_date->format('Y-m-d') }})
                    @endif
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">{{ $isAr ? 'عدد الأشقاء' : 'Siblings Count' }}</div>
                <div class="value">{{ $beneficiary->siblings_count ?? '—' }}</div>
            </td>
            <td>
                <div class="label">{{ $isAr ? 'الوضع المادي' : 'Financial Status' }}</div>
                <div class="value">
                    {{ $beneficiary->financial_status
                        ? ($isAr
                            ? __("beneficiaries.{$beneficiary->financial_status}")
                            : ucfirst($beneficiary->financial_status))
                        : '—' }}
                </div>
            </td>
        </tr>
    </table>
    @if ($beneficiary->financial_notes)
        <p style="font-size:11px; color:#536060; margin-top:4px;">
            <strong>{{ $isAr ? 'ملاحظات مادية:' : 'Financial Notes:' }}</strong>
            {{ $beneficiary->financial_notes }}
        </p>
    @endif

    {{-- ── الحالة الطبية ── --}}
    <h2>{{ $isAr ? 'الحالة الطبية' : 'Medical Status' }}</h2>
    <table class="grid">
        <tr>
            <td>
                <div class="label">{{ $isAr ? 'نوع الإعاقة' : 'Disability Type' }}</div>
                <div class="value">{{ $beneficiary->disability_type ?? '—' }}</div>
            </td>
            <td>
                <div class="label">{{ $isAr ? 'درجة الإعاقة' : 'Disability Degree' }}</div>
                <div class="value">
                    {{ $beneficiary->disability_degree
                        ? ($isAr
                            ? __("beneficiaries.{$beneficiary->disability_degree}")
                            : ucfirst($beneficiary->disability_degree))
                        : '—' }}
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">{{ $isAr ? 'الطبيب' : 'Doctor' }}</div>
                <div class="value">{{ $beneficiary->doctor_name ?? '—' }}</div>
            </td>
            <td>
                <div class="label">{{ $isAr ? 'المستشفى' : 'Hospital' }}</div>
                <div class="value">{{ $beneficiary->hospital_name ?? '—' }}</div>
            </td>
        </tr>
    </table>
    @if ($beneficiary->medical_notes)
        <p style="font-size:11px; color:#536060; margin-top:4px;">
            <strong>{{ $isAr ? 'ملاحظات طبية:' : 'Medical Notes:' }}</strong>
            {{ $beneficiary->medical_notes }}
        </p>
    @endif

    {{-- ── الأدوية ── --}}
    @if ($beneficiary->medications->count() > 0)
        <h2>{{ $isAr ? 'الأدوية النشطة' : 'Active Medications' }}</h2>
        <table class="data">
            <thead>
                <tr>
                    <th>{{ $isAr ? 'اسم الدواء' : 'Medication' }}</th>
                    <th>{{ $isAr ? 'الجرعة' : 'Dosage' }}</th>
                    <th>{{ $isAr ? 'التكرار' : 'Frequency' }}</th>
                    <th>{{ $isAr ? 'التوقيت' : 'Timing' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($beneficiary->medications as $med)
                    <tr>
                        <td>{{ $med->name }}</td>
                        <td>{{ $med->dosage }}</td>
                        <td>{{ $med->frequency }} {{ $isAr ? 'مرات/يوم' : 'times/day' }}</td>
                        <td>{{ $isAr ? __("medical.{$med->timing}") : ucfirst($med->timing) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- ── آخر 10 افتقادات ── --}}
    @if ($beneficiary->visits->count() > 0)
        <h2>{{ $isAr ? 'آخر 10 افتقادات' : 'Last 10 Visits' }}</h2>
        <table class="data">
            <thead>
                <tr>
                    <th>{{ $isAr ? 'التاريخ' : 'Date' }}</th>
                    <th>{{ $isAr ? 'النوع' : 'Type' }}</th>
                    <th>{{ $isAr ? 'الحالة' : 'Status' }}</th>
                    <th>{{ $isAr ? 'الخادم' : 'Servant' }}</th>
                    <th>{{ $isAr ? 'ملاحظات' : 'Notes' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($beneficiary->visits as $visit)
                    <tr>
                        <td>{{ $visit->visit_date?->format('Y-m-d') }}</td>
                        <td>{{ $isAr ? __("visits.{$visit->type}") : ucfirst($visit->type) }}</td>
                        <td class="badge-{{ $visit->beneficiary_status }}">
                            {{ $isAr ? __("visits.{$visit->beneficiary_status}") : ucfirst($visit->beneficiary_status) }}
                        </td>
                        <td>{{ $visit->createdBy?->name ?? '—' }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($visit->feedback, 40) ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p
        style="text-align:center; margin-top:20px; color:#8F9E9E; font-size:10px; border-top: 1px solid #E8F0F0; padding-top: 8px;">
        {{ $isAr ? 'نظام إدارة خدمة ذوي الاحتياجات الخاصة' : 'Special Needs Ministry Management System' }}
        — {{ now()->format('Y-m-d H:i') }}
    </p>

</body>

</html>
