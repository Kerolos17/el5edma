<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servant UI Preview Lab</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Cairo:wght@300;400;600;700&family=Tajawal:wght@500;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --teal-900: #003942;
            --teal-700: #005662;
            --teal-500: #006d77;
            --teal-300: #4d9ba3;
            --teal-100: #c7e5e8;
            --gold-700: #d68a3d;
            --gold-500: #f4a261;
            --gold-300: #f7bb86;
            --gold-100: #fdebd0;
            --cream: #fff8f0;
            --warm-white: #fffbf7;
            --soft-gray: #f5f3f0;
            --text-dark: #2c3e50;
            --text-muted: #7a8b99;
            --critical: #e63946;
            --success: #06a77d;
            --shadow-soft: 0 24px 60px rgba(0, 57, 66, 0.08);
            --radius-xl: 28px;
            --radius-lg: 22px;
            --radius-md: 18px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Cairo', sans-serif;
            background:
                radial-gradient(circle at top right, rgba(244, 162, 97, 0.18), transparent 30%),
                radial-gradient(circle at top left, rgba(0, 109, 119, 0.16), transparent 28%),
                linear-gradient(180deg, #fffdf9 0%, var(--cream) 45%, #fff 100%);
            color: var(--text-dark);
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image: radial-gradient(rgba(0, 57, 66, 0.045) 0.7px, transparent 0.7px);
            background-size: 18px 18px;
            opacity: 0.28;
            pointer-events: none;
        }

        .lab-shell {
            position: relative;
            max-width: 1400px;
            margin: 0 auto;
            padding: 32px 20px 80px;
        }

        .lab-header {
            position: sticky;
            top: 0;
            z-index: 30;
            backdrop-filter: blur(18px);
            background: rgba(255, 251, 247, 0.85);
            border: 1px solid rgba(0, 109, 119, 0.08);
            box-shadow: 0 10px 30px rgba(0, 57, 66, 0.06);
            border-radius: 24px;
            padding: 20px 22px;
            margin-bottom: 28px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(244, 162, 97, 0.16);
            color: var(--gold-700);
            font-size: 13px;
            font-weight: 700;
        }

        .lab-title {
            margin: 14px 0 8px;
            font-family: 'Amiri', serif;
            font-size: clamp(2rem, 4vw, 3.4rem);
            line-height: 1.2;
        }

        .lab-subtitle {
            margin: 0;
            max-width: 920px;
            font-size: 16px;
            color: var(--text-muted);
            line-height: 1.9;
        }

        .header-note {
            margin-top: 16px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .header-pill {
            padding: 9px 14px;
            border-radius: 999px;
            background: rgba(0, 109, 119, 0.08);
            color: var(--teal-700);
            font-size: 13px;
            font-weight: 700;
        }

        .jump-links {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        .jump-links a {
            text-decoration: none;
            color: var(--teal-900);
            background: white;
            border-radius: 999px;
            padding: 10px 14px;
            font-size: 14px;
            font-weight: 700;
            border: 1px solid rgba(0, 57, 66, 0.08);
        }

        .concept {
            position: relative;
            margin-bottom: 48px;
            padding: 28px;
            border-radius: 32px;
            background: rgba(255, 255, 255, 0.72);
            box-shadow: var(--shadow-soft);
            overflow: hidden;
        }

        .concept::before {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            background: radial-gradient(circle at top right, rgba(244, 162, 97, 0.12), transparent 32%);
        }

        .concept-head {
            position: relative;
            display: grid;
            grid-template-columns: 1.2fr .8fr;
            gap: 20px;
            align-items: start;
            margin-bottom: 24px;
        }

        .concept-title {
            margin: 10px 0 8px;
            font-size: clamp(1.5rem, 2.8vw, 2.4rem);
            font-family: 'Amiri', serif;
        }

        .concept-copy {
            margin: 0;
            color: var(--text-muted);
            line-height: 1.9;
            font-size: 15px;
        }

        .concept-meta {
            display: grid;
            gap: 12px;
        }

        .meta-card {
            background: rgba(255, 255, 255, 0.86);
            border: 1px solid rgba(0, 57, 66, 0.08);
            border-radius: 20px;
            padding: 16px 18px;
        }

        .meta-label {
            display: block;
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .meta-value {
            font-weight: 800;
            font-size: 15px;
        }

        .screens {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .screen {
            position: relative;
            background: white;
            border-radius: 26px;
            border: 1px solid rgba(0, 57, 66, 0.08);
            box-shadow: 0 18px 36px rgba(0, 57, 66, 0.07);
            padding: 18px;
            min-height: 340px;
        }

        .screen-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(0, 109, 119, 0.08);
            color: var(--teal-700);
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 14px;
        }

        .hero-card {
            border-radius: 26px;
            padding: 20px;
            color: white;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, var(--teal-500), var(--teal-900));
        }

        .hero-card.gold {
            background: linear-gradient(135deg, var(--gold-500), #e76f51);
        }

        .hero-card::after {
            content: '';
            position: absolute;
            width: 180px;
            height: 180px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.16), transparent 68%);
            top: -50px;
            left: -30px;
            border-radius: 50%;
        }

        .hero-card h3,
        .hero-card p,
        .hero-card .hero-actions,
        .hero-card .hero-kicker {
            position: relative;
            z-index: 1;
        }

        .hero-kicker {
            font-size: 13px;
            opacity: 0.9;
        }

        .hero-card h3 {
            margin: 10px 0 6px;
            font-size: 28px;
            font-family: 'Amiri', serif;
        }

        .hero-card p {
            margin: 0;
            opacity: 0.92;
            line-height: 1.8;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        .action-chip {
            background: rgba(255, 255, 255, 0.17);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 16px;
            padding: 10px 12px;
            font-size: 13px;
            font-weight: 700;
        }

        .stat-row {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-top: 16px;
        }

        .stat-tile {
            border-radius: 20px;
            padding: 16px;
            background: linear-gradient(to bottom, rgba(255,255,255,0.9), rgba(255,248,240,0.95));
            border: 1px solid rgba(0, 57, 66, 0.06);
        }

        .stat-tile .value {
            display: block;
            font-family: 'Tajawal', sans-serif;
            font-size: 30px;
            font-weight: 900;
            color: var(--teal-900);
            line-height: 1;
            margin-bottom: 8px;
        }

        .stat-tile .label {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.7;
        }

        .stack {
            display: grid;
            gap: 14px;
            margin-top: 16px;
        }

        .beneficiary-card,
        .board-card,
        .profile-card,
        .step-card,
        .schedule-card,
        .alert-card {
            border-radius: 22px;
            background: var(--warm-white);
            border: 1px solid rgba(0, 57, 66, 0.07);
            padding: 16px;
        }

        .beneficiary-row {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .avatar {
            width: 58px;
            height: 58px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--gold-300), var(--gold-100));
            color: var(--teal-900);
            font-weight: 900;
            box-shadow: inset 0 0 0 4px rgba(255, 255, 255, 0.65);
        }

        .beneficiary-info h4,
        .board-card h4,
        .profile-card h4,
        .alert-card h4,
        .schedule-card h4,
        .step-card h4 {
            margin: 0 0 6px;
            font-size: 16px;
        }

        .muted {
            color: var(--text-muted);
            font-size: 13px;
            line-height: 1.8;
        }

        .tag-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }

        .tag {
            padding: 7px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
        }

        .tag.teal {
            color: var(--teal-700);
            background: rgba(0, 109, 119, 0.1);
        }

        .tag.gold {
            color: var(--gold-700);
            background: rgba(244, 162, 97, 0.16);
        }

        .tag.red {
            color: #b12233;
            background: rgba(230, 57, 70, 0.12);
        }

        .actions-inline {
            display: flex;
            gap: 10px;
            margin-top: 16px;
        }

        .button,
        .button-outline,
        .icon-button {
            border: none;
            border-radius: 16px;
            font-family: inherit;
            cursor: default;
        }

        .button {
            background: linear-gradient(135deg, var(--teal-500), var(--teal-700));
            color: white;
            padding: 12px 14px;
            font-size: 13px;
            font-weight: 800;
        }

        .button-outline {
            background: rgba(0, 109, 119, 0.08);
            color: var(--teal-700);
            padding: 12px 14px;
            font-size: 13px;
            font-weight: 800;
        }

        .icon-button {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #35d37e, #25d366);
            color: white;
            font-weight: 900;
        }

        .list-table {
            display: grid;
            gap: 10px;
        }

        .list-row {
            display: grid;
            grid-template-columns: 1.1fr 0.8fr 0.8fr 0.9fr;
            gap: 10px;
            align-items: center;
            border-radius: 18px;
            padding: 14px;
            background: white;
            border: 1px solid rgba(0, 57, 66, 0.06);
        }

        .split-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .timeline {
            display: grid;
            gap: 12px;
            position: relative;
        }

        .timeline::before {
            content: '';
            position: absolute;
            right: 16px;
            top: 4px;
            bottom: 4px;
            width: 2px;
            background: linear-gradient(to bottom, rgba(0, 109, 119, 0.55), transparent);
        }

        .timeline-item {
            position: relative;
            margin-right: 18px;
            padding-right: 22px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            right: 0;
            top: 7px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--gold-500);
            box-shadow: 0 0 0 6px rgba(244, 162, 97, 0.18);
        }

        .stepper {
            display: grid;
            gap: 12px;
            margin-top: 14px;
        }

        .step-card {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .step-badge {
            min-width: 42px;
            min-height: 42px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--gold-500), var(--gold-700));
            color: white;
            font-weight: 900;
        }

        .concept-quick {
            border-top: 5px solid rgba(244, 162, 97, 0.9);
        }

        .concept-board {
            border-top: 5px solid rgba(0, 109, 119, 0.9);
        }

        .concept-centered {
            border-top: 5px solid rgba(214, 138, 61, 0.9);
        }

        .footer-note {
            margin-top: 20px;
            padding: 16px 18px;
            border-radius: 20px;
            background: rgba(0, 109, 119, 0.06);
            color: var(--teal-700);
            font-size: 14px;
            line-height: 1.9;
        }

        @media (max-width: 980px) {
            .concept-head,
            .screens,
            .split-grid,
            .stat-row,
            .list-row {
                grid-template-columns: 1fr;
            }

            .lab-header {
                position: static;
            }

            .concept {
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="lab-shell">
        <div class="lab-header">
            <span class="eyebrow">Servant UI Lab</span>
            <h1 class="lab-title">معرض واجهات الخدام قبل التنفيذ الفعلي</h1>
            <p class="lab-subtitle">
                الداشبورد الحالية الخاصة بـ <strong>مدير النظام</strong> و<strong>أمين الخدمة</strong> ستظل كما هي.
                الصفحة دي مجرد معمل مقارنة لواجهة الخادم فقط، بحيث تختار الاتجاه الأقرب لطريقة شغلك قبل ما نبدأ التطبيق على الصفحات الحقيقية.
            </p>

            <div class="header-note">
                <span class="header-pill">لن نلمس Dashboard الأدمن الحالية</span>
                <span class="header-pill">التركيز الآن على Servant Experience</span>
                <span class="header-pill">Preview فقط قبل التطبيق</span>
            </div>

            <div class="jump-links">
                <a href="#quick-mission">Quick Mission</a>
                <a href="#care-board">Care Board</a>
                <a href="#beneficiary-centered">Beneficiary-Centered</a>
            </div>
        </div>

        <section id="quick-mission" class="concept concept-quick">
            <div class="concept-head">
                <div>
                    <span class="eyebrow">Direction A</span>
                    <h2 class="concept-title">Quick Mission</h2>
                    <p class="concept-copy">
                        اتجاه مبني على السرعة القصوى. الخادم يدخل يجد أهم الأكشنز فورًا، يسجل زيارة بسرعة، ويعرف مين يحتاج متابعة الآن بدون تشتيت.
                        مناسب جدًا لو أهم هدف هو تقليل الضغطات وتسهيل الخدمة اليومية على الموبايل.
                    </p>
                </div>

                <div class="concept-meta">
                    <div class="meta-card">
                        <span class="meta-label">الأنسب لـ</span>
                        <div class="meta-value">Servant الميداني السريع</div>
                    </div>
                    <div class="meta-card">
                        <span class="meta-label">الانطباع</span>
                        <div class="meta-value">دافئ، سريع، مباشر</div>
                    </div>
                </div>
            </div>

            <div class="screens">
                <div class="screen">
                    <div class="screen-label">Home</div>
                    <div class="hero-card gold">
                        <div class="hero-kicker">{{ $servant['greeting'] }}</div>
                        <h3>{{ $servant['name'] }}</h3>
                        <p>كل شيء جاهز الآن. افتح أسرع مهمة وابدأ الخدمة من أول لحظة.</p>
                        <div class="hero-actions">
                            @foreach ($quickActions as $action)
                                <span class="action-chip">{{ $action['label'] }}</span>
                            @endforeach
                        </div>
                    </div>

                    <div class="stat-row">
                        @foreach ($stats as $stat)
                            <div class="stat-tile">
                                <span class="value">{{ $stat['value'] }}</span>
                                <span class="label">{{ $stat['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="screen">
                    <div class="screen-label">Follow Up List</div>
                    <div class="stack">
                        @foreach ($beneficiaries as $beneficiary)
                            <div class="beneficiary-card">
                                <div class="beneficiary-row">
                                    <div class="avatar">{{ mb_substr($beneficiary['name'], 0, 1) }}</div>
                                    <div class="beneficiary-info">
                                        <h4>{{ $beneficiary['name'] }}</h4>
                                        <div class="muted">آخر زيارة: {{ $beneficiary['lastVisit'] }}</div>
                                    </div>
                                </div>

                                <div class="tag-row">
                                    <span class="tag teal">{{ $beneficiary['status'] }}</span>
                                    <span class="tag {{ $beneficiary['priorityTone'] }}">{{ $beneficiary['priority'] }}</span>
                                </div>

                                <div class="actions-inline">
                                    <button class="icon-button">WA</button>
                                    <button class="button-outline">افتح الملف</button>
                                    <button class="button">سجل زيارة</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="screen">
                    <div class="screen-label">Beneficiary Profile</div>
                    <div class="profile-card">
                        <div class="beneficiary-row">
                            <div class="avatar">م</div>
                            <div class="beneficiary-info">
                                <h4>مريم نبيل</h4>
                                <div class="muted">أسرة مارمرقس • متابعة أسبوعية</div>
                            </div>
                        </div>

                        <div class="tag-row">
                            <span class="tag teal">نشط</span>
                            <span class="tag gold">تحتاج متابعة</span>
                        </div>

                        <div class="actions-inline">
                            <button class="button">سجل زيارة</button>
                            <button class="button-outline">زيارة مجدولة</button>
                            <button class="icon-button">WA</button>
                        </div>

                        <div class="timeline" style="margin-top:18px;">
                            @foreach ($timeline as $item)
                                <div class="timeline-item">
                                    <h4>{{ $item['title'] }}</h4>
                                    <div class="muted">{{ $item['time'] }}</div>
                                    <div class="muted">{{ $item['body'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="screen">
                    <div class="screen-label">Record Visit</div>
                    <div class="hero-card">
                        <div class="hero-kicker">Flow سريع جدًا</div>
                        <h3>تسجيل زيارة في أقل خطوات</h3>
                        <p>نفس المهمة لكن بأوضح تسلسل، حتى لا يضيع الخادم وسط التفاصيل.</p>
                    </div>

                    <div class="stepper">
                        @foreach ($visitSteps as $index => $step)
                            <div class="step-card">
                                <span class="step-badge">{{ $index + 1 }}</span>
                                <div>
                                    <h4>{{ $step['title'] }}</h4>
                                    <div class="muted">{{ $step['hint'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section id="care-board" class="concept concept-board">
            <div class="concept-head">
                <div>
                    <span class="eyebrow">Direction B</span>
                    <h2 class="concept-title">Care Board</h2>
                    <p class="concept-copy">
                        هذا الاتجاه أقرب إلى لوحة تشغيل يومية. يوازن بين وضوح الأرقام، متابعة اليوم، والتنبيهات المهمة، بدون أن يفقد الدفء البصري.
                        مناسب عندما تريد رؤية أوسع من مجرد زر سريع، مع إحساس قوي بأن اليوم كله تحت السيطرة.
                    </p>
                </div>

                <div class="concept-meta">
                    <div class="meta-card">
                        <span class="meta-label">الأنسب لـ</span>
                        <div class="meta-value">الخادم المنظم أو أمين الأسرة لاحقًا</div>
                    </div>
                    <div class="meta-card">
                        <span class="meta-label">الانطباع</span>
                        <div class="meta-value">تشغيلي، واضح، متوازن</div>
                    </div>
                </div>
            </div>

            <div class="screens">
                <div class="screen">
                    <div class="screen-label">Home Board</div>
                    <div class="hero-card">
                        <div class="hero-kicker">لوحة اليوم</div>
                        <h3>صورة كاملة لليوم</h3>
                        <p>تشوف اليوم، الأولويات، والخطوات التالية في مكان واحد.</p>
                    </div>

                    <div class="stat-row">
                        @foreach ($stats as $stat)
                            <div class="stat-tile">
                                <span class="value">{{ $stat['value'] }}</span>
                                <span class="label">{{ $stat['label'] }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="split-grid" style="margin-top:16px;">
                        <div class="schedule-card">
                            <h4>زيارات اليوم</h4>
                            <div class="stack">
                                @foreach ($schedule as $slot)
                                    <div>
                                        <strong>{{ $slot['time'] }}</strong>
                                        <div class="muted">{{ $slot['title'] }} • {{ $slot['person'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="alert-card">
                            <h4>تنبيهات مهمة</h4>
                            <div class="stack">
                                @foreach ($alerts as $alert)
                                    <div class="board-card" style="background: {{ $alert['tone'] === 'red' ? 'rgba(230,57,70,0.07)' : 'rgba(244,162,97,0.10)' }};">
                                        <h4>{{ $alert['title'] }}</h4>
                                        <div class="muted">{{ $alert['body'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="screen">
                    <div class="screen-label">Beneficiaries Table</div>
                    <div class="board-card" style="margin-bottom: 14px;">
                        <h4>قائمة المخدومين</h4>
                        <div class="muted">بحث سريع + فلتر حسب الأولوية أو آخر زيارة</div>
                    </div>

                    <div class="list-table">
                        @foreach ($beneficiaries as $beneficiary)
                            <div class="list-row">
                                <div>
                                    <strong>{{ $beneficiary['name'] }}</strong>
                                    <div class="muted">{{ $beneficiary['note'] }}</div>
                                </div>
                                <div class="muted">{{ $beneficiary['status'] }}</div>
                                <div class="muted">{{ $beneficiary['lastVisit'] }}</div>
                                <div style="display:flex; gap:8px; justify-content:flex-end;">
                                    <button class="button-outline">افتح</button>
                                    <button class="button">زيارة</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="screen">
                    <div class="screen-label">Beneficiary Snapshot</div>
                    <div class="split-grid">
                        <div class="profile-card">
                            <h4>ملف مختصر</h4>
                            <div class="muted">الاسم: مريم نبيل</div>
                            <div class="muted">الخادم: {{ $servant['name'] }}</div>
                            <div class="muted">آخر زيارة: منذ 3 أيام</div>
                            <div class="tag-row">
                                <span class="tag teal">نشط</span>
                                <span class="tag gold">تحتاج متابعة</span>
                            </div>
                        </div>
                        <div class="profile-card">
                            <h4>الأولوية التالية</h4>
                            <div class="muted">أفضل قرار الآن هو تسجيل زيارة منزلية جديدة مع ملاحظة متابعة للأسرة.</div>
                            <div class="actions-inline">
                                <button class="button">سجل زيارة</button>
                                <button class="button-outline">أضف متابعة</button>
                            </div>
                        </div>
                    </div>

                    <div class="timeline" style="margin-top:16px;">
                        @foreach ($timeline as $item)
                            <div class="timeline-item">
                                <h4>{{ $item['title'] }}</h4>
                                <div class="muted">{{ $item['time'] }}</div>
                                <div class="muted">{{ $item['body'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="screen">
                    <div class="screen-label">Visit Form Board</div>
                    <div class="board-card">
                        <h4>خطوات واضحة مع إشارات متابعة</h4>
                        <div class="muted">هذا الاتجاه يضيف وضوحًا أكثر للحالة والتنبيه أثناء كتابة الزيارة.</div>
                    </div>
                    <div class="stepper">
                        @foreach ($visitSteps as $index => $step)
                            <div class="step-card">
                                <span class="step-badge">{{ $index + 1 }}</span>
                                <div>
                                    <h4>{{ $step['title'] }}</h4>
                                    <div class="muted">{{ $step['hint'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section id="beneficiary-centered" class="concept concept-centered">
            <div class="concept-head">
                <div>
                    <span class="eyebrow">Direction C</span>
                    <h2 class="concept-title">Beneficiary-Centered</h2>
                    <p class="concept-copy">
                        هذا الاتجاه يدور كله حول المخدوم نفسه، وليس لوحة الأرقام. الخادم يركز على الشخص والحالة والتاريخ والزيارة التالية.
                        الأفضل عندما تكون المتابعة الدقيقة أهم من السرعة المطلقة.
                    </p>
                </div>

                <div class="concept-meta">
                    <div class="meta-card">
                        <span class="meta-label">الأنسب لـ</span>
                        <div class="meta-value">متابعة الحالات والملف الشخصي</div>
                    </div>
                    <div class="meta-card">
                        <span class="meta-label">الانطباع</span>
                        <div class="meta-value">إنساني، عميق، متابع للحالة</div>
                    </div>
                </div>
            </div>

            <div class="screens">
                <div class="screen">
                    <div class="screen-label">Home Search Flow</div>
                    <div class="hero-card gold">
                        <div class="hero-kicker">ابدأ من الشخص نفسه</div>
                        <h3>ابحث عن المخدوم أولًا</h3>
                        <p>هذه الصفحة تقلل التشتيت وتبدأ من الاحتياج الفعلي للخادم: من أتابع الآن؟</p>
                    </div>

                    <div class="board-card" style="margin-top:16px;">
                        <h4>بحث سريع</h4>
                        <div class="muted">ابحث بالاسم أو الهاتف أو الكود داخل المجموعة</div>
                        <div style="margin-top: 14px; border-radius: 18px; background: white; border: 1px solid rgba(0,57,66,0.07); padding: 14px; color: var(--text-muted);">
                            مريم نبيل / يوسف عادل / سارة مجدي
                        </div>
                    </div>

                    <div class="stack">
                        @foreach ($alerts as $alert)
                            <div class="alert-card" style="margin-top: 12px; background: {{ $alert['tone'] === 'red' ? 'rgba(230,57,70,0.07)' : 'rgba(244,162,97,0.10)' }};">
                                <h4>{{ $alert['title'] }}</h4>
                                <div class="muted">{{ $alert['body'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="screen">
                    <div class="screen-label">Profile First</div>
                    <div class="profile-card">
                        <div class="beneficiary-row">
                            <div class="avatar">ي</div>
                            <div class="beneficiary-info">
                                <h4>يوسف عادل</h4>
                                <div class="muted">آخر زيارة: منذ 8 أيام • أولوية عالية</div>
                            </div>
                        </div>

                        <div class="tag-row">
                            <span class="tag red">أولوية عالية</span>
                            <span class="tag teal">داخل مجموعتي</span>
                        </div>

                        <div class="footer-note">
                            ملخص الحالة في أول الشاشة بدل ما الخادم يفتش داخل Tabs كثيرة قبل ما يفهم الصورة الكاملة.
                        </div>
                    </div>

                    <div class="timeline" style="margin-top:16px;">
                        @foreach ($timeline as $item)
                            <div class="timeline-item">
                                <h4>{{ $item['title'] }}</h4>
                                <div class="muted">{{ $item['time'] }}</div>
                                <div class="muted">{{ $item['body'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="screen">
                    <div class="screen-label">Beneficiaries as Cases</div>
                    <div class="stack">
                        @foreach ($beneficiaries as $beneficiary)
                            <div class="beneficiary-card">
                                <h4>{{ $beneficiary['name'] }}</h4>
                                <div class="muted">{{ $beneficiary['priority'] }} • {{ $beneficiary['lastVisit'] }}</div>
                                <div class="footer-note" style="margin-top:12px;">
                                    {{ $beneficiary['note'] }}
                                </div>
                                <div class="actions-inline">
                                    <button class="button">افتح الملف</button>
                                    <button class="button-outline">سجل زيارة</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="screen">
                    <div class="screen-label">Visit with Context</div>
                    <div class="board-card">
                        <h4>تسجيل زيارة مع سياق مخدوم واضح</h4>
                        <div class="muted">قبل التسجيل، الخادم يرى آخر زيارة والحالة الحالية والتوصية القادمة.</div>
                    </div>
                    <div class="stepper">
                        @foreach ($visitSteps as $index => $step)
                            <div class="step-card">
                                <span class="step-badge">{{ $index + 1 }}</span>
                                <div>
                                    <h4>{{ $step['title'] }}</h4>
                                    <div class="muted">{{ $step['hint'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <div class="footer-note">
            الخطوة التالية بعد اختيارك: سأحوّل الاتجاه الذي اخترته إلى شاشات حقيقية للـ <strong>Servant</strong> فقط،
            مع إبقاء Dashboard الحالية الخاصة بـ <strong>مدير النظام</strong> و<strong>أمين الخدمة</strong> بدون أي تغيير.
        </div>
    </div>
</body>

</html>
