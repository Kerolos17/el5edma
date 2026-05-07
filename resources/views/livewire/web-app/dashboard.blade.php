<section class="app-page-stack">
    <div class="app-hero-panel">
        <div>
            <p class="app-section-label">{{ $roleLabel }}</p>
            <h2>مساحة عمل موحدة لمتابعة الخدمة اليومية</h2>
            <p>واجهة Web App حديثة تجمع البيانات المهمة حسب دورك، مع بقاء لوحة Filament كمسار احتياطي أثناء الانتقال.</p>
        </div>
        <div class="app-hero-actions">
            <a href="{{ route('app.beneficiaries') }}" wire:navigate class="app-primary-button">
                <i class="ph ph-users-three" aria-hidden="true"></i>
                المخدومون
            </a>
            <a href="{{ route('app.visits') }}" wire:navigate class="app-secondary-button">
                <i class="ph ph-clipboard-text" aria-hidden="true"></i>
                الزيارات
            </a>
        </div>
    </div>

    <div class="app-stat-grid">
        @foreach ($stats as $stat)
            <article class="app-stat-card tone-{{ $stat['tone'] }}">
                <div class="app-stat-icon">
                    <i class="ph {{ $stat['icon'] }}" aria-hidden="true"></i>
                </div>
                <div>
                    <p>{{ $stat['label'] }}</p>
                    <strong>{{ number_format($stat['value']) }}</strong>
                </div>
            </article>
        @endforeach
    </div>

    <div class="app-dashboard-grid">
        <section class="app-panel">
            <div class="app-panel-header">
                <div>
                    <p class="app-section-label">النشاط الأخير</p>
                    <h3>آخر الزيارات</h3>
                </div>
                <a href="{{ route('app.visits') }}" wire:navigate>عرض الكل</a>
            </div>

            <div class="app-activity-list">
                @forelse ($recentVisits as $visit)
                    <article class="app-activity-row">
                        <div>
                            <strong>{{ $visit->beneficiary?->full_name ?? 'بدون اسم' }}</strong>
                            <span>{{ $visit->createdBy?->name ?? 'غير محدد' }}</span>
                        </div>
                        <time>{{ $visit->visit_date?->format('Y-m-d') }}</time>
                    </article>
                @empty
                    <div class="app-empty-state">
                        <i class="ph ph-clipboard-text" aria-hidden="true"></i>
                        <p>لا توجد زيارات حديثة في نطاقك الحالي.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <aside class="app-panel">
            <div class="app-panel-header">
                <div>
                    <p class="app-section-label">مؤشرات جانبية</p>
                    <h3>جاهزية النظام</h3>
                </div>
            </div>

            <dl class="app-mini-metrics">
                <div>
                    <dt>طلبات صلاة مفتوحة</dt>
                    <dd>{{ number_format($secondaryStats['openPrayerRequests']) }}</dd>
                </div>
                <div>
                    <dt>ملفات طبية</dt>
                    <dd>{{ number_format($secondaryStats['medicalFiles']) }}</dd>
                </div>
                @if ($secondaryStats['users'] !== null)
                    <div>
                        <dt>مستخدمون في النطاق</dt>
                        <dd>{{ number_format($secondaryStats['users']) }}</dd>
                    </div>
                @endif
                @if ($secondaryStats['serviceGroups'] !== null)
                    <div>
                        <dt>مجموعات خدمة</dt>
                        <dd>{{ number_format($secondaryStats['serviceGroups']) }}</dd>
                    </div>
                @endif
            </dl>
        </aside>
    </div>
</section>
