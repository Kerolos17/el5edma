@php
    $b = $beneficiary;
    $age = $b->birth_date ? $b->birth_date->age : null;

    $statusColors = [
        'active'   => ['text' => '#06A77D', 'bg' => 'rgba(6,167,125,0.12)',  'label' => 'نشط'],
        'inactive' => ['text' => '#457B9D', 'bg' => 'rgba(69,123,157,0.12)', 'label' => 'غير نشط'],
        'default'  => ['text' => '#F77F00', 'bg' => 'rgba(247,127,0,0.12)',  'label' => $b->status ?? ''],
    ];
    $sc = $statusColors[$b->status] ?? $statusColors['default'];

    $visitTypeLabels = [
        'home_visit'     => 'زيارة منزلية',
        'phone_call'     => 'مكالمة هاتفية',
        'church_meeting' => 'اجتماع كنيسة',
    ];
    $visitStatusLabels = [
        'great'        => ['label' => 'ممتاز',        'color' => '#06A77D'],
        'good'         => ['label' => 'جيد',          'color' => '#4D9BA3'],
        'needs_follow' => ['label' => 'يحتاج متابعة', 'color' => '#F77F00'],
        'critical'     => ['label' => 'حرج',          'color' => '#E63946'],
    ];
@endphp

<div class="pb-32 lg:pb-10">

    {{-- Secondary Header (back nav) --}}
    <div class="sticky top-[65px] lg:top-0 z-30 px-4 py-3 flex items-center gap-3"
         style="background: rgba(255,251,247,0.95); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(0,109,119,0.07);">
        <a href="{{ route('servant.beneficiaries') }}" wire:navigate
           class="w-9 h-9 rounded-xl bg-teal-50 flex items-center justify-center flex-shrink-0 hover:bg-teal-100 transition-colors"
           aria-label="رجوع للمخدومين">
            <i class="ph-bold ph-arrow-right text-teal-700 text-lg" aria-hidden="true"></i>
        </a>
        <h1 class="font-bold text-teal-900 text-base truncate flex-1">{{ $b->full_name }}</h1>
        <span class="badge-pill text-xs px-3 py-1 flex-shrink-0"
              style="background: {{ $sc['bg'] }}; color: {{ $sc['text'] }}; font-weight: 700;">
            {{ $sc['label'] }}
        </span>
    </div>

    <div class="px-4 pt-5 space-y-5">

        {{-- ══════════ Hero Card ══════════ --}}
        <div class="s-card reveal-card rounded-3xl overflow-hidden">
            {{-- Gradient banner --}}
            <div class="h-20 gradient-deep relative" aria-hidden="true">
                <div class="absolute inset-0 opacity-10"
                     style="background: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(255,255,255,0.1) 10px, rgba(255,255,255,0.1) 20px);">
                </div>
            </div>

            <div class="px-5 pt-0 pb-5">
                {{-- Avatar overlaps gradient; name below on white --}}
                <div class="-mt-8 mb-4">
                    <x-ui.avatar
                        :name="$b->full_name"
                        :src="$b->photo_url ?? null"
                        size="xl"
                        shape="square"
                        gradient="gold"
                        class="avatar-ring relative z-10 mb-3"
                    />
                    <p class="font-bold text-teal-900 text-lg leading-tight">{{ $b->full_name }}</p>
                    <p class="text-sm text-gray-400 mt-0.5" dir="ltr">{{ $b->code }}</p>
                </div>

                {{-- Info chips row --}}
                <div class="flex flex-wrap gap-2 mb-4">
                    @if($age)
                        <span class="badge-pill badge-info text-xs">
                            <i class="ph ph-cake text-xs" aria-hidden="true"></i>
                            {{ $age }} سنة
                        </span>
                    @endif
                    @if($b->gender)
                        <span class="badge-pill badge-info text-xs">
                            <i class="ph ph-gender-{{ $b->gender === 'male' ? 'male' : 'female' }} text-xs" aria-hidden="true"></i>
                            {{ $b->gender === 'male' ? 'ذكر' : 'أنثى' }}
                        </span>
                    @endif
                    @if($b->area)
                        <span class="badge-pill badge-info text-xs">
                            <i class="ph ph-map-pin text-xs" aria-hidden="true"></i>
                            {{ $b->area }}
                        </span>
                    @endif
                    @if($b->disability_type)
                        <span class="badge-pill badge-warning text-xs">
                            <i class="ph ph-wheelchair text-xs" aria-hidden="true"></i>
                            {{ $b->disability_type }}
                        </span>
                    @endif
                </div>

                @if($b->assignedServant)
                    <div class="flex items-center gap-2 mb-4">
                        <x-ui.avatar
                            :name="$b->assignedServant->name"
                            :src="$b->assignedServant->profile_photo_url ?? null"
                            size="xs"
                            shape="round"
                        />
                        <p class="text-xs text-gray-400">
                            الخادم المسؤول:
                            <span class="font-semibold text-teal-800">{{ $b->assignedServant->name }}</span>
                        </p>
                    </div>
                @endif

                {{-- Quick Actions --}}
                <div class="space-y-2">
                    @php
                        $hasPhone    = !empty($b->phone);
                        $hasWhatsapp = !empty($b->whatsapp_url);
                        $hasMaps     = !empty($b->google_maps_url) && str_starts_with($b->google_maps_url, 'https://');
                    @endphp

                    @if($hasPhone || $hasWhatsapp || $hasMaps)
                        <div class="flex gap-2">
                            @if($hasWhatsapp)
                                <a href="{{ $b->whatsapp_url }}" target="_blank" rel="noopener"
                                   class="btn-whatsapp"
                                   aria-label="تواصل عبر واتساب">
                                    <i class="ph-fill ph-whatsapp-logo text-lg" aria-hidden="true"></i>
                                </a>
                            @endif
                            @if($hasPhone)
                                <a href="tel:{{ $b->phone }}"
                                   class="w-10 h-10 rounded-full flex items-center justify-center transition-all duration-200 hover:scale-110 flex-shrink-0"
                                   style="background: linear-gradient(135deg, #457B9D, #6BA3C7); box-shadow: 0 2px 8px rgba(69,123,157,0.3);"
                                   aria-label="اتصال هاتفي">
                                    <i class="ph-fill ph-phone text-white text-lg" aria-hidden="true"></i>
                                </a>
                            @endif
                            @if($hasMaps)
                                <a href="{{ $b->google_maps_url }}" target="_blank" rel="noopener noreferrer"
                                   class="w-10 h-10 rounded-full flex items-center justify-center transition-all duration-200 hover:scale-110 flex-shrink-0"
                                   style="background: linear-gradient(135deg, #EA4335, #FBBC04); box-shadow: 0 2px 8px rgba(234,67,53,0.25);"
                                   aria-label="فتح الموقع على الخريطة">
                                    <i class="ph-fill ph-map-pin text-white text-lg" aria-hidden="true"></i>
                                </a>
                            @endif
                        </div>
                    @endif

                    <button wire:click="visitThis"
                            class="w-full py-2.5 rounded-2xl flex items-center justify-center gap-2 font-bold text-sm text-white btn-ripple"
                            style="background: linear-gradient(135deg, #006D77 0%, #003942 100%); box-shadow: 0 4px 16px rgba(0,109,119,0.3);">
                        <i class="ph-bold ph-calendar-plus text-base" aria-hidden="true"></i>
                        تسجيل زيارة
                    </button>
                </div>
            </div>
        </div>

        {{-- ══════════ Recent Visits ══════════ --}}
        <div class="reveal-card" style="animation-delay: 0.08s">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-bold text-teal-900">آخر الزيارات</h2>
                <span class="text-xs text-gray-400">{{ $recentVisits->count() }} زيارة</span>
            </div>

            @if($recentVisits->isEmpty())
                <x-ui.empty-state icon="ph-calendar-blank" message="لا توجد زيارات مسجلة" size="sm" />
            @else
                <div class="space-y-2">
                    @foreach($recentVisits as $visit)
                        <div class="s-card rounded-2xl px-4 py-3 flex items-start gap-3"
                             style="{{ $visit->is_critical ? 'border-right: 3px solid #E63946;' : '' }}"
                             role="article">
                            {{-- Date --}}
                            <div class="text-center flex-shrink-0 w-12" aria-hidden="true">
                                <p class="text-xl font-bold text-teal-700 leading-none" style="font-family: var(--font-accent);">
                                    {{ $visit->visit_date->format('d') }}
                                </p>
                                <p class="text-[10px] text-gray-400 leading-tight mt-0.5">
                                    {{ $visit->visit_date->format('M Y') }}
                                </p>
                            </div>

                            <div class="w-px self-stretch bg-gray-100" aria-hidden="true"></div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    @if(isset($visitTypeLabels[$visit->type]))
                                        <span class="text-xs font-semibold text-teal-700">{{ $visitTypeLabels[$visit->type] }}</span>
                                    @endif
                                    @if(isset($visitStatusLabels[$visit->beneficiary_status]))
                                        <span class="text-xs font-semibold"
                                              style="color: {{ $visitStatusLabels[$visit->beneficiary_status]['color'] }}">
                                            {{ $visitStatusLabels[$visit->beneficiary_status]['label'] }}
                                        </span>
                                    @endif
                                    @if($visit->is_critical)
                                        <span class="badge-pill badge-critical text-xs px-2 py-0.5 critical-indicator">
                                            <i class="ph-fill ph-warning text-xs" aria-hidden="true"></i> حرجة
                                        </span>
                                    @endif
                                </div>
                                @if($visit->feedback)
                                    <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ $visit->feedback }}</p>
                                @endif
                                @if($visit->duration_minutes)
                                    <p class="text-xs text-gray-400 mt-1">
                                        <i class="ph ph-clock text-xs" aria-hidden="true"></i> {{ $visit->duration_minutes }} دقيقة
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ══════════ Active Medications ══════════ --}}
        @if($activeMedications->isNotEmpty())
            <div class="reveal-card" style="animation-delay: 0.12s">
                <x-ui.section-header
                    icon="ph-fill ph-pill"
                    title="الأدوية الحالية"
                    color="red"
                    class="mb-3"
                />

                <div class="space-y-2">
                    @foreach($activeMedications as $med)
                        <div class="s-card rounded-2xl px-4 py-3">
                            <div class="flex items-start gap-3">
                                <div class="w-2 h-2 rounded-full mt-2 flex-shrink-0" style="background: #E63946" aria-hidden="true"></div>
                                <div>
                                    <p class="font-bold text-sm text-teal-900">{{ $med->name }}</p>
                                    <div class="flex flex-wrap gap-x-3 gap-y-0.5 mt-0.5">
                                        @if($med->dosage)
                                            <span class="text-xs text-gray-500">
                                                <i class="ph ph-scales text-xs" aria-hidden="true"></i> {{ $med->dosage }}
                                            </span>
                                        @endif
                                        @if($med->frequency)
                                            <span class="text-xs text-gray-500">
                                                <i class="ph ph-clock-countdown text-xs" aria-hidden="true"></i> {{ $med->frequency }}
                                            </span>
                                        @endif
                                        @if($med->timing)
                                            <span class="text-xs text-gray-500">{{ $med->timing }}</span>
                                        @endif
                                    </div>
                                    @if($med->notes)
                                        <p class="text-xs text-gray-400 mt-1">{{ $med->notes }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ══════════ Prayer Requests ══════════ --}}
        @if($openPrayerRequests->isNotEmpty())
            <div class="reveal-card" style="animation-delay: 0.16s">
                <x-ui.section-header
                    icon="ph-fill ph-hands-praying"
                    title="طلبات الصلاة"
                    color="teal"
                    class="mb-3"
                />

                <div class="space-y-2">
                    @foreach($openPrayerRequests as $prayer)
                        <div class="s-card rounded-2xl px-4 py-3">
                            <p class="font-bold text-sm text-teal-900">{{ $prayer->title }}</p>
                            @if($prayer->body)
                                <p class="text-xs text-gray-500 mt-1 leading-relaxed">{{ $prayer->body }}</p>
                            @endif
                            <p class="text-xs text-gray-400 mt-2">
                                {{ $prayer->created_at->locale('ar')->isoFormat('D MMM YYYY') }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ══════════ Basic Info ══════════ --}}
        <div class="reveal-card" style="animation-delay: 0.2s">
            <x-ui.section-header
                icon="ph-fill ph-info"
                title="المعلومات الأساسية"
                color="teal"
                class="mb-3"
            />

            <div class="s-card rounded-2xl px-4 py-3 space-y-3">
                @php
                    $infoRows = array_values(array_filter([
                        ['ph-birthday',      'تاريخ الميلاد',   $b->birth_date ? $b->birth_date->locale('ar')->isoFormat('D MMMM YYYY') . ($age ? " ({$age} سنة)" : '') : null],
                        ['ph-map-pin',       'العنوان',         trim(($b->area ?? '') . ($b->governorate ? '، ' . $b->governorate : '')) ?: $b->address_text],
                        ['ph-user',          'ولي الأمر',       $b->guardian_name ? $b->guardian_name . ($b->guardian_relation ? " ({$b->guardian_relation})" : '') : null],
                        ['ph-phone',         'هاتف ولي الأمر',  $b->guardian_phone],
                        ['ph-users-three',   'الإخوة',          $b->siblings_count ? $b->siblings_count . ' إخوة' . ($b->siblings_note ? " — {$b->siblings_note}" : '') : null],
                        ['ph-wheelchair',    'الإعاقة',         $b->disability_type ? $b->disability_type . ($b->disability_degree ? " — {$b->disability_degree}" : '') : null],
                        ['ph-heartbeat',     'الحالة الصحية',   $b->health_status],
                        ['ph-stethoscope',   'الطبيب',          $b->doctor_name ? $b->doctor_name . ($b->hospital_name ? " / {$b->hospital_name}" : '') : null],
                    ], fn ($row) => ! empty($row[2])));
                @endphp

                @forelse($infoRows as $i => [$icon, $label, $value])
                    @if($i > 0)
                        <div class="h-px bg-gray-50" aria-hidden="true"></div>
                    @endif
                    <div class="flex items-start gap-3">
                        <div class="w-7 h-7 rounded-lg bg-teal-50 flex items-center justify-center flex-shrink-0 mt-0.5" aria-hidden="true">
                            <i class="ph {{ $icon }} text-sm text-teal-600"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs text-gray-400">{{ $label }}</p>
                            <p class="text-sm font-semibold text-teal-900 mt-0.5 leading-snug">{{ $value }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 text-center py-2">لا توجد بيانات مضافة</p>
                @endforelse
            </div>
        </div>

        {{-- ══════════ Medical Notes ══════════ --}}
        @if($b->medical_notes)
            <div class="reveal-card" style="animation-delay: 0.24s">
                <x-ui.section-header
                    icon="ph-fill ph-note-pencil"
                    title="ملاحظات طبية"
                    color="red"
                    class="mb-3"
                />
                <div class="s-card rounded-2xl px-4 py-3">
                    <p class="text-sm text-gray-600 leading-relaxed">{{ $b->medical_notes }}</p>
                    @if($b->last_medical_update)
                        <p class="text-xs text-gray-400 mt-2">
                            آخر تحديث: {{ $b->last_medical_update->locale('ar')->isoFormat('D MMM YYYY') }}
                        </p>
                    @endif
                </div>
            </div>
        @endif

    </div>
</div>
