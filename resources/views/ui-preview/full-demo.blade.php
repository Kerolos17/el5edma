<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ministry Frontend Full Demo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Cairo:wght@300;400;600;700&family=Tajawal:wght@500;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --teal-950: #022f36;
            --teal-900: #003942;
            --teal-700: #005662;
            --teal-500: #006d77;
            --teal-300: #4d9ba3;
            --teal-100: #c7e5e8;
            --gold-700: #d68a3d;
            --gold-500: #f4a261;
            --gold-300: #f7bb86;
            --gold-100: #fdebd0;
            --rose-500: #e76f51;
            --warm-white: #fffbf7;
            --cream: #fff8f0;
            --text-dark: #243746;
            --text-muted: #6d7f8a;
            --border: rgba(0, 57, 66, 0.08);
            --shadow-soft: 0 20px 50px rgba(0, 57, 66, 0.09);
            --shadow-card: 0 14px 36px rgba(0, 57, 66, 0.08);
            --radius-2xl: 32px;
            --radius-xl: 26px;
            --radius-lg: 22px;
            --radius-md: 18px;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            font-family: 'Cairo', sans-serif;
            color: var(--text-dark);
            background:
                radial-gradient(circle at top right, rgba(244, 162, 97, 0.18), transparent 24%),
                radial-gradient(circle at top left, rgba(0, 109, 119, 0.16), transparent 24%),
                linear-gradient(180deg, #fffdf9 0%, var(--cream) 45%, white 100%);
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            pointer-events: none;
            background-image: radial-gradient(rgba(0, 57, 66, 0.04) 0.75px, transparent 0.75px);
            background-size: 18px 18px;
            opacity: 0.28;
        }

        .demo-page {
            position: relative;
            max-width: 1480px;
            margin: 0 auto;
            padding: 28px 18px 72px;
        }

        .hero {
            position: relative;
            overflow: hidden;
            border-radius: var(--radius-2xl);
            padding: 28px;
            background: linear-gradient(135deg, rgba(255, 251, 247, 0.94), rgba(255, 248, 240, 0.96));
            border: 1px solid rgba(0, 109, 119, 0.08);
            box-shadow: var(--shadow-soft);
            margin-bottom: 22px;
        }

        .hero::after {
            content: '';
            position: absolute;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            top: -100px;
            left: -60px;
            background: radial-gradient(circle, rgba(244, 162, 97, 0.18), transparent 68%);
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(244, 162, 97, 0.18);
            color: var(--gold-700);
            font-size: 13px;
            font-weight: 800;
        }

        .hero h1 {
            position: relative;
            z-index: 1;
            margin: 16px 0 10px;
            font-family: 'Amiri', serif;
            font-size: clamp(2rem, 4vw, 3.7rem);
            line-height: 1.2;
        }

        .hero p {
            position: relative;
            z-index: 1;
            margin: 0;
            max-width: 900px;
            color: var(--text-muted);
            line-height: 1.9;
            font-size: 16px;
        }

        .hero-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        .pill {
            padding: 9px 14px;
            border-radius: 999px;
            background: rgba(0, 109, 119, 0.08);
            color: var(--teal-700);
            font-size: 13px;
            font-weight: 800;
        }

        .radio-set {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .switcher {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 0 0 22px;
        }

        .switcher label {
            border-radius: 999px;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid var(--border);
            cursor: pointer;
            font-size: 14px;
            font-weight: 800;
            color: var(--text-dark);
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }

        .switcher label:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(0, 57, 66, 0.08);
        }

        .stage {
            display: grid;
            grid-template-columns: 290px minmax(0, 1fr);
            gap: 18px;
            align-items: start;
        }

        .sidebar {
            position: sticky;
            top: 20px;
            border-radius: var(--radius-2xl);
            background: linear-gradient(180deg, var(--teal-950), var(--teal-700));
            color: white;
            padding: 22px;
            box-shadow: var(--shadow-soft);
            overflow: hidden;
        }

        .sidebar::before {
            content: '';
            position: absolute;
            inset: auto -30px -40px auto;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255,255,255,0.14), transparent 68%);
        }

        .brand {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }

        .brand-mark {
            width: 44px;
            height: 44px;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--gold-500), var(--rose-500));
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
        }

        .brand strong {
            display: block;
            font-size: 16px;
        }

        .brand span {
            display: block;
            font-size: 13px;
            opacity: 0.74;
            margin-top: 3px;
        }

        .sidebar-menu {
            display: grid;
            gap: 10px;
        }

        .sidebar-item {
            border-radius: 18px;
            padding: 12px 14px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.08);
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .sidebar-item.active {
            background: rgba(255,255,255,0.16);
        }

        .sidebar-note {
            margin-top: 18px;
            border-radius: 20px;
            padding: 16px;
            background: rgba(255,255,255,0.08);
            font-size: 13px;
            line-height: 1.9;
            color: rgba(255,255,255,0.84);
        }

        .main-demo {
            border-radius: var(--radius-2xl);
            background: rgba(255,255,255,0.82);
            border: 1px solid rgba(0, 57, 66, 0.08);
            box-shadow: var(--shadow-soft);
            padding: 18px;
            min-height: 820px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 14px 16px;
            border-radius: 20px;
            background: rgba(255,255,255,0.86);
            border: 1px solid var(--border);
            margin-bottom: 18px;
        }

        .topbar h2 {
            margin: 0;
            font-family: 'Amiri', serif;
            font-size: 28px;
        }

        .topbar p {
            margin: 6px 0 0;
            color: var(--text-muted);
            font-size: 13px;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .round-chip,
        .soft-chip,
        .primary-btn,
        .secondary-btn,
        .mini-btn,
        .wa-btn {
            border: none;
            font-family: inherit;
        }

        .round-chip {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(0, 109, 119, 0.08);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--teal-700);
            font-weight: 900;
        }

        .soft-chip {
            padding: 9px 12px;
            border-radius: 999px;
            background: rgba(244, 162, 97, 0.14);
            color: var(--gold-700);
            font-size: 12px;
            font-weight: 800;
        }

        .primary-btn {
            border-radius: 18px;
            padding: 12px 14px;
            background: linear-gradient(135deg, var(--teal-500), var(--teal-700));
            color: white;
            font-size: 13px;
            font-weight: 800;
        }

        .secondary-btn {
            border-radius: 18px;
            padding: 12px 14px;
            background: rgba(0, 109, 119, 0.08);
            color: var(--teal-700);
            font-size: 13px;
            font-weight: 800;
        }

        .mini-btn {
            border-radius: 14px;
            padding: 9px 12px;
            background: rgba(255,255,255,0.16);
            color: inherit;
            font-size: 12px;
            font-weight: 800;
        }

        .wa-btn {
            width: 46px;
            height: 46px;
            border-radius: 18px;
            background: linear-gradient(135deg, #35d37e, #25d366);
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
        }

        .panel {
            display: none;
        }

        #screen-home:checked ~ .switcher label[for="screen-home"],
        #screen-directory:checked ~ .switcher label[for="screen-directory"],
        #screen-profile:checked ~ .switcher label[for="screen-profile"],
        #screen-visit:checked ~ .switcher label[for="screen-visit"],
        #screen-notifications:checked ~ .switcher label[for="screen-notifications"],
        #screen-family:checked ~ .switcher label[for="screen-family"] {
            background: linear-gradient(135deg, var(--teal-500), var(--teal-700));
            color: white;
            border-color: transparent;
        }

        #screen-home:checked ~ .stage .panel-home,
        #screen-directory:checked ~ .stage .panel-directory,
        #screen-profile:checked ~ .stage .panel-profile,
        #screen-visit:checked ~ .stage .panel-visit,
        #screen-notifications:checked ~ .stage .panel-notifications,
        #screen-family:checked ~ .stage .panel-family {
            display: block;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }

        .hero-card {
            border-radius: 28px;
            padding: 24px;
            color: white;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, var(--teal-500), var(--teal-900));
            box-shadow: var(--shadow-card);
        }

        .hero-card.sunrise {
            background: linear-gradient(135deg, var(--gold-500), var(--rose-500));
        }

        .hero-card::after {
            content: '';
            position: absolute;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            top: -90px;
            left: -30px;
            background: radial-gradient(circle, rgba(255,255,255,0.16), transparent 68%);
        }

        .hero-card > * {
            position: relative;
            z-index: 1;
        }

        .hero-card h3 {
            margin: 10px 0 8px;
            font-family: 'Amiri', serif;
            font-size: clamp(1.7rem, 3vw, 2.8rem);
        }

        .hero-card p {
            margin: 0;
            line-height: 1.9;
            opacity: 0.94;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .stat-card {
            border-radius: 22px;
            padding: 18px;
            background: linear-gradient(to bottom, rgba(255,255,255,0.96), rgba(255,248,240,0.96));
            border: 1px solid rgba(0, 57, 66, 0.06);
        }

        .stat-card strong {
            display: block;
            font-family: 'Tajawal', sans-serif;
            font-size: 30px;
            line-height: 1;
            margin-bottom: 8px;
        }

        .stat-card span {
            color: var(--text-muted);
            font-size: 13px;
            line-height: 1.7;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1.2fr .8fr;
            gap: 16px;
            margin-top: 16px;
        }

        .surface,
        .list-item,
        .profile-surface,
        .timeline-card,
        .step-card,
        .notification-card,
        .queue-card,
        .summary-card {
            border-radius: 24px;
            background: var(--warm-white);
            border: 1px solid rgba(0, 57, 66, 0.07);
            box-shadow: 0 12px 28px rgba(0, 57, 66, 0.06);
            padding: 18px;
        }

        .surface h4,
        .profile-surface h4,
        .queue-card h4,
        .notification-card h4,
        .summary-card h4 {
            margin: 0 0 8px;
            font-size: 17px;
        }

        .surface p,
        .profile-surface p,
        .queue-card p,
        .summary-card p,
        .subtle {
            margin: 0;
            color: var(--text-muted);
            line-height: 1.9;
            font-size: 13px;
        }

        .quick-list,
        .queue-list,
        .notifications-list,
        .timeline-list,
        .steps-list {
            display: grid;
            gap: 12px;
            margin-top: 16px;
        }

        .list-item {
            display: flex;
            gap: 14px;
            align-items: flex-start;
        }

        .avatar {
            width: 58px;
            height: 58px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--gold-300), var(--gold-100));
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--teal-900);
            font-weight: 900;
            flex-shrink: 0;
        }

        .item-body {
            flex: 1;
        }

        .item-body strong {
            display: block;
            font-size: 16px;
            margin-bottom: 4px;
        }

        .tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }

        .tag {
            border-radius: 999px;
            padding: 7px 12px;
            font-size: 12px;
            font-weight: 800;
        }

        .tag.teal {
            background: rgba(0, 109, 119, 0.08);
            color: var(--teal-700);
        }

        .tag.gold {
            background: rgba(244, 162, 97, 0.16);
            color: var(--gold-700);
        }

        .tag.red {
            background: rgba(230, 57, 70, 0.1);
            color: #b22738;
        }

        .inline-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 16px;
        }

        .list-table {
            display: grid;
            gap: 10px;
            margin-top: 16px;
        }

        .table-row {
            display: grid;
            grid-template-columns: 1.2fr .8fr .8fr auto;
            gap: 10px;
            align-items: center;
            border-radius: 18px;
            padding: 14px;
            background: white;
            border: 1px solid rgba(0, 57, 66, 0.06);
        }

        .profile-layout {
            display: grid;
            grid-template-columns: .95fr 1.05fr;
            gap: 16px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 16px;
        }

        .summary-card strong {
            display: block;
            font-size: 16px;
            margin-bottom: 8px;
        }

        .timeline-list {
            position: relative;
            margin-top: 14px;
        }

        .timeline-list::before {
            content: '';
            position: absolute;
            right: 18px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, rgba(0, 109, 119, 0.6), transparent);
        }

        .timeline-card {
            position: relative;
            margin-right: 18px;
            padding-right: 26px;
        }

        .timeline-card::before {
            content: '';
            position: absolute;
            right: 0;
            top: 8px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--gold-500);
            box-shadow: 0 0 0 6px rgba(244, 162, 97, 0.18);
        }

        .steps-list {
            margin-top: 16px;
        }

        .step-card {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .step-badge {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--gold-500), var(--gold-700));
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            flex-shrink: 0;
        }

        .notification-card.critical {
            background: linear-gradient(to bottom, rgba(230,57,70,0.08), rgba(255,255,255,0.96));
        }

        .notification-card.warning {
            background: linear-gradient(to bottom, rgba(244,162,97,0.12), rgba(255,255,255,0.96));
        }

        .family-board {
            display: grid;
            gap: 16px;
        }

        .family-kpis {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .family-panels {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .queue-list li {
            color: var(--text-muted);
            margin-bottom: 10px;
            line-height: 1.8;
        }

        .admin-note {
            margin-top: 18px;
            border-radius: 24px;
            padding: 18px 20px;
            background: rgba(0, 109, 119, 0.06);
            color: var(--teal-700);
            line-height: 1.9;
            font-size: 14px;
        }

        @media (max-width: 1100px) {
            .stage,
            .hero-grid,
            .content-grid,
            .profile-layout,
            .family-panels {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: static;
            }
        }

        @media (max-width: 820px) {
            .stat-grid,
            .family-kpis,
            .summary-grid,
            .table-row {
                grid-template-columns: 1fr;
            }

            .topbar {
                flex-direction: column;
                align-items: stretch;
            }

            .switcher {
                display: grid;
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="demo-page">
        <section class="hero">
            <span class="eyebrow">HTML &amp; CSS Full Demo</span>
            <h1>Demo كاملة لواجهة المشروع بعد التعديل قبل التنفيذ الحقيقي</h1>
            <p>
                هنا ستشاهد الموقع كأنه تجربة كاملة، وليس فقط كروت منفصلة. الفكرة أن ترى كيف ستعمل الواجهة الجديدة للـ
                <strong>Servant</strong> وطبقة إضافية لـ <strong>Family Leader</strong>، بينما تبقى داشبورد
                <strong>مدير النظام</strong> و<strong>أمين الخدمة</strong> الحالية كما هي بدون أي تغيير.
            </p>

            <div class="hero-pills">
                <span class="pill">Frontend جديدة للخدام</span>
                <span class="pill">Warm + Human UI</span>
                <span class="pill">Mobile-ready</span>
                <span class="pill">Admin dashboard unchanged</span>
            </div>
        </section>

        <input class="radio-set" type="radio" name="screen" id="screen-home" checked>
        <input class="radio-set" type="radio" name="screen" id="screen-directory">
        <input class="radio-set" type="radio" name="screen" id="screen-profile">
        <input class="radio-set" type="radio" name="screen" id="screen-visit">
        <input class="radio-set" type="radio" name="screen" id="screen-notifications">
        <input class="radio-set" type="radio" name="screen" id="screen-family">

        <div class="switcher">
            <label for="screen-home">Home</label>
            <label for="screen-directory">Beneficiaries</label>
            <label for="screen-profile">Beneficiary Profile</label>
            <label for="screen-visit">Record Visit</label>
            <label for="screen-notifications">Notifications</label>
            <label for="screen-family">Family Leader Board</label>
        </div>

        <div class="stage">
            <aside class="sidebar">
                <div class="brand">
                    <span class="brand-mark">✦</span>
                    <div>
                        <strong>Compassionate Ministry</strong>
                        <span>واجهة خادم دافئة وسريعة</span>
                    </div>
                </div>

                <div class="sidebar-menu">
                    <div class="sidebar-item active"><span>الرئيسية</span><span>⌂</span></div>
                    <div class="sidebar-item"><span>المخدومين</span><span>◎</span></div>
                    <div class="sidebar-item"><span>الزيارات</span><span>✎</span></div>
                    <div class="sidebar-item"><span>المجدول</span><span>◔</span></div>
                    <div class="sidebar-item"><span>الإشعارات</span><span>●</span></div>
                </div>

                <div class="sidebar-note">
                    الـ shell دي مقصودة للخدام والواجهات التشغيلية اليومية. أما الـ Dashboard الحالية الخاصة بمدير النظام وأمين الخدمة فلن تتغير.
                </div>
            </aside>

            <main class="main-demo">
                <div class="topbar">
                    <div>
                        <h2>Frontend Demo</h2>
                        <p>بدّل بين الشاشات من الأزرار بالأعلى وحدد أي تجربة أقرب لطريقة شغلك الفعلي</p>
                    </div>

                    <div class="topbar-actions">
                        <span class="soft-chip">{{ $servant['team'] }}</span>
                        <button class="round-chip">🔔</button>
                        <button class="primary-btn">جاهز للتحويل بعد الاختيار</button>
                    </div>
                </div>

                <section class="panel panel-home">
                    <div class="hero-grid">
                        <div class="hero-card sunrise">
                            <div class="soft-chip" style="display:inline-flex; background: rgba(255,255,255,.18); color: white;">{{ $servant['greeting'] }}</div>
                            <h3>{{ $servant['name'] }}</h3>
                            <p>الصفحة الرئيسية هنا مبنية لتبدأ من أهم شيء: ما الذي أحتاج فعله الآن؟ بدون زحمة ولا widgets إدارية كثيرة.</p>
                            <div class="hero-actions">
                                @foreach ($quickActions as $action)
                                    <button class="mini-btn">{{ $action['label'] }}</button>
                                @endforeach
                            </div>
                        </div>

                        <div class="hero-card">
                            <div class="soft-chip" style="display:inline-flex; background: rgba(255,255,255,.16); color: white;">أولوية اليوم</div>
                            <h3>3 زيارات + 2 تنبيه مهم</h3>
                            <p>الصورة العامة لليوم حاضرة من أول ثانية: الزيارات، التنبيهات، وأقرب أكشن سريع.</p>
                        </div>
                    </div>

                    <div class="stat-grid">
                        @foreach ($stats as $stat)
                            <div class="stat-card">
                                <strong>{{ $stat['value'] }}</strong>
                                <span>{{ $stat['label'] }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="content-grid">
                        <div class="surface">
                            <h4>مخدومون يحتاجون متابعة الآن</h4>
                            <p>بدل ما الخادم يفتش في صفحات كثيرة، أهم الحالات تظهر هنا مباشرة.</p>

                            <div class="quick-list">
                                @foreach ($beneficiaries as $beneficiary)
                                    <div class="list-item">
                                        <span class="avatar">{{ mb_substr($beneficiary['name'], 0, 1) }}</span>
                                        <div class="item-body">
                                            <strong>{{ $beneficiary['name'] }}</strong>
                                            <div class="subtle">آخر زيارة: {{ $beneficiary['lastVisit'] }}</div>
                                            <div class="tags">
                                                <span class="tag teal">{{ $beneficiary['status'] }}</span>
                                                <span class="tag {{ $beneficiary['priorityTone'] }}">{{ $beneficiary['priority'] }}</span>
                                            </div>
                                            <div class="inline-actions">
                                                <button class="wa-btn">WA</button>
                                                <button class="secondary-btn">افتح الملف</button>
                                                <button class="primary-btn">سجل زيارة</button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="surface">
                            <h4>زيارات اليوم</h4>
                            <p>جانب سريع يعطي الخادم إحساسًا واضحًا بإيقاع يومه.</p>
                            <div class="queue-list">
                                @foreach ($schedule as $slot)
                                    <div class="queue-card">
                                        <h4>{{ $slot['time'] }} • {{ $slot['title'] }}</h4>
                                        <p>{{ $slot['person'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>

                <section class="panel panel-directory">
                    <div class="surface">
                        <h4>Beneficiaries Directory</h4>
                        <p>قائمة المخدومين هنا متصممة كأداة متابعة، لا كجدول إداري جاف.</p>

                        <div class="inline-actions" style="margin-top: 14px;">
                            <button class="secondary-btn">بحث بالاسم أو الهاتف</button>
                            <button class="secondary-btn">فلتر حسب الأولوية</button>
                            <button class="secondary-btn">آخر زيارة</button>
                        </div>

                        <div class="list-table">
                            @foreach ($beneficiaries as $beneficiary)
                                <div class="table-row">
                                    <div>
                                        <strong>{{ $beneficiary['name'] }}</strong>
                                        <div class="subtle">{{ $beneficiary['note'] }}</div>
                                    </div>
                                    <div class="subtle">{{ $beneficiary['status'] }}</div>
                                    <div class="subtle">{{ $beneficiary['lastVisit'] }}</div>
                                    <div class="inline-actions" style="margin-top:0; justify-content:end;">
                                        <button class="secondary-btn">افتح</button>
                                        <button class="primary-btn">زيارة</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section class="panel panel-profile">
                    <div class="profile-layout">
                        <div class="profile-surface">
                            <div class="list-item" style="padding:0; box-shadow:none; border:0; background:transparent;">
                                <span class="avatar" style="width:72px; height:72px;">م</span>
                                <div class="item-body">
                                    <strong style="font-size:22px; margin-bottom:6px;">مريم نبيل</strong>
                                    <div class="subtle">{{ $servant['team'] }} • آخر زيارة منذ 3 أيام • متابعة أسبوعية</div>
                                    <div class="tags">
                                        <span class="tag teal">نشط</span>
                                        <span class="tag gold">تحتاج متابعة</span>
                                    </div>
                                </div>
                            </div>

                            <div class="summary-grid">
                                <div class="summary-card">
                                    <strong>ملخص الحالة</strong>
                                    <p>الحالة النفسية مستقرة لكن المتابعة الأسرية مطلوبة هذا الأسبوع.</p>
                                </div>
                                <div class="summary-card">
                                    <strong>أفضل خطوة الآن</strong>
                                    <p>زيارة منزلية قصيرة مع تدوين متابعة واضحة للأسرة.</p>
                                </div>
                                <div class="summary-card">
                                    <strong>قنوات سريعة</strong>
                                    <p>واتساب • مكالمة • جدولة زيارة</p>
                                </div>
                                <div class="summary-card">
                                    <strong>تنبيه مهم</strong>
                                    <p>يوجد طلب صلاة لم يُتابع منذ أسبوع.</p>
                                </div>
                            </div>

                            <div class="inline-actions">
                                <button class="primary-btn">سجل زيارة</button>
                                <button class="secondary-btn">زيارة مجدولة</button>
                                <button class="wa-btn">WA</button>
                            </div>
                        </div>

                        <div class="profile-surface">
                            <h4>Timeline</h4>
                            <p>هذه الصفحة تجعل الملف الشخصي هو مركز التجربة بدل التنقل بين Tabs كثيرة.</p>

                            <div class="timeline-list">
                                @foreach ($timeline as $item)
                                    <div class="timeline-card">
                                        <h4>{{ $item['title'] }}</h4>
                                        <p class="subtle">{{ $item['time'] }}</p>
                                        <p class="subtle">{{ $item['body'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>

                <section class="panel panel-visit">
                    <div class="hero-card">
                        <div class="soft-chip" style="display:inline-flex; background: rgba(255,255,255,.16); color: white;">Record Visit Flow</div>
                        <h3>تسجيل زيارة بوضوح أكثر</h3>
                        <p>الهدف هنا هو تقليل التشتت وجعل تسجيل الزيارة أقرب لتسلسل منطقي سريع وسهل على الموبايل.</p>
                    </div>

                    <div class="content-grid" style="margin-top: 16px;">
                        <div class="surface">
                            <h4>الخطوات</h4>
                            <div class="steps-list">
                                @foreach ($visitSteps as $index => $step)
                                    <div class="step-card">
                                        <span class="step-badge">{{ $index + 1 }}</span>
                                        <div>
                                            <h4>{{ $step['title'] }}</h4>
                                            <p class="subtle">{{ $step['hint'] }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="surface">
                            <h4>لماذا هذه الصيغة أفضل؟</h4>
                            <div class="queue-list">
                                <div class="queue-card">
                                    <h4>المخدوم أولًا</h4>
                                    <p>الخادم لا يبدأ من form فارغة، بل من شخص واضح وحالة واضحة.</p>
                                </div>
                                <div class="queue-card">
                                    <h4>الحالة لا تضيع</h4>
                                    <p>الحالة والمتابعة والتنبيه تظهر في نفس السياق بدل أن تكون حقولًا مفككة.</p>
                                </div>
                                <div class="queue-card">
                                    <h4>أفضل للموبايل</h4>
                                    <p>تقسيم الخطوات يخفف الزحمة ويقلل نسبة الخطأ أثناء التسجيل.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="panel panel-notifications">
                    <div class="content-grid" style="grid-template-columns: .9fr 1.1fr;">
                        <div class="surface">
                            <h4>Notifications Center</h4>
                            <p>الإشعارات هنا ليست مجرد bell صغيرة، لكنها مركز متابعة عملي ومرتب.</p>
                            <div class="notifications-list">
                                @foreach ($alerts as $alert)
                                    <div class="notification-card {{ $alert['tone'] === 'red' ? 'critical' : 'warning' }}">
                                        <h4>{{ $alert['title'] }}</h4>
                                        <p>{{ $alert['body'] }}</p>
                                        <div class="inline-actions">
                                            <button class="primary-btn">افتح الحالة</button>
                                            <button class="secondary-btn">تحديد كمقروء</button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="surface">
                            <h4>شكل البل بعد التحسين</h4>
                            <p>الإشعارات المهمة ستكون أوضح وأهدأ بصريًا من الـ admin patterns التقليدية.</p>
                            <div class="notifications-list">
                                <div class="notification-card critical">
                                    <h4>يوسف يحتاج تدخل سريع</h4>
                                    <p>هذا شكل للإشعار الحرج بلون ونبرة أقوى تساعد على أخذ القرار بسرعة.</p>
                                </div>
                                <div class="notification-card warning">
                                    <h4>تذكير بزيارة مريم</h4>
                                    <p>الإشعار المتوسط الأهمية يظل واضحًا لكن أقل حدة من الحالة الحرجة.</p>
                                </div>
                                <div class="notification-card">
                                    <h4>عيد ميلاد قريب</h4>
                                    <p>الإشعارات اللطيفة تبقى دافئة ومبهجة بدون شكل إداري جامد.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="panel panel-family">
                    <div class="hero-grid">
                        <div class="hero-card">
                            <div class="soft-chip" style="display:inline-flex; background: rgba(255,255,255,.16); color: white;">Family Leader Layer</div>
                            <h3>{{ $familyLeader['name'] }}</h3>
                            <p>{{ $familyLeader['focus'] }} بنفس الهوية البصرية، لكن بعرض أوسع للمتابعة اليومية والقرارات.</p>
                        </div>

                        <div class="hero-card sunrise">
                            <div class="soft-chip" style="display:inline-flex; background: rgba(255,255,255,.16); color: white;">Note</div>
                            <h3>هذا ليس Dashboard الأدمن</h3>
                            <p>هذه طبقة تشغيلية جديدة لأمين الأسرة، وليست بديلًا عن الداشبورد الحالية الخاصة بمدير النظام وأمين الخدمة.</p>
                        </div>
                    </div>

                    <div class="family-board">
                        <div class="family-kpis">
                            @foreach ($familyBoard['today'] as $card)
                                <div class="stat-card">
                                    <strong>{{ $card['value'] }}</strong>
                                    <span>{{ $card['label'] }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="family-panels">
                            @foreach ($familyBoard['queues'] as $queue)
                                <div class="surface">
                                    <h4>{{ $queue['title'] }}</h4>
                                    <ul class="queue-list" style="padding: 0 18px 0 0; margin: 14px 0 0;">
                                        @foreach ($queue['items'] as $item)
                                            <li>{{ $item }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            </main>
        </div>

        <div class="admin-note">
            <strong>الذي فهمته وسأبني عليه لو اخترت هذا الاتجاه:</strong><br>
            1. الداشبورد الحالية لـ <strong>مدير النظام</strong> و<strong>أمين الخدمة</strong> ستظل كما هي.<br>
            2. الواجهة الجديدة تستهدف بالأساس <strong>الخدام</strong>، ويمكن بعدها توسيع بعض أجزائها لـ <strong>أمين الأسرة</strong> لو رغبت.<br>
            3. أول 4 شاشات حقيقية للتحويل ستكون: <strong>Home</strong>، <strong>Beneficiaries</strong>، <strong>Beneficiary Profile</strong>، <strong>Record Visit</strong>.
        </div>
    </div>
</body>

</html>
