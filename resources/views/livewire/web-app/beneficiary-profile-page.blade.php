<section class="app-page-stack">
    <x-slot:title>{{ $beneficiary->full_name }}</x-slot:title>

    {{-- Hero Section --}}
    <div class="app-hero-panel">
        <div class="flex items-start gap-4 sm:gap-6 flex-wrap sm:flex-nowrap">
            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl overflow-hidden ring-4 ring-white/30 flex-shrink-0 flex items-center justify-center" style="background: var(--clr-soft-frost)">
                @if ($beneficiary->photo_url)
                    <img src="{{ $beneficiary->photo_url }}" alt="{{ $beneficiary->full_name }}" class="w-full h-full object-cover">
                @else
                    <span class="text-2xl sm:text-3xl font-bold app-text-muted">{{ mb_substr($beneficiary->full_name, 0, 1) }}</span>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <h2 class="text-xl sm:text-2xl font-bold">{{ $beneficiary->full_name }}</h2>
                    <span class="app-muted-badge">{{ $beneficiary->code ?: __('web_app.fallback.no_code') }}</span>
                    <span class="app-muted-badge app-badge-{{ $beneficiary->status ?? 'active' }}">
                        {{ $beneficiary->status ? __("beneficiaries.{$beneficiary->status}") : __('beneficiaries.active') }}
                    </span>
                </div>
                <p class="mt-1 text-sm app-text-muted">
                    {{ $beneficiary->serviceGroup?->name ?? __('web_app.fallback.unassigned_feminine') }}
                    @if ($beneficiary->assignedServant)
                        &middot; {{ $beneficiary->assignedServant->name }}
                    @endif
                </p>
                <div class="flex gap-2 mt-3 flex-wrap">
                    @if ($beneficiary->whatsapp_url)
                        <a href="{{ $beneficiary->whatsapp_url }}" target="_blank" class="app-secondary-button app-hero-button">
                            <i class="ph-fill ph-whatsapp-logo" aria-hidden="true"></i>
                            WhatsApp
                        </a>
                    @endif
                    @if ($beneficiary->phone)
                        <a href="tel:{{ $beneficiary->phone }}" class="app-secondary-button app-hero-button">
                            <i class="ph ph-phone" aria-hidden="true"></i>
                            {{ __('web_app.table.phone') }}
                        </a>
                    @endif
                    @if ($beneficiary->google_maps_url)
                        <a href="{{ $beneficiary->google_maps_url }}" target="_blank" class="app-secondary-button app-hero-button">
                            <i class="ph ph-map-pin" aria-hidden="true"></i>
                            {{ __('web_app.table.address') }}
                        </a>
                    @endif
                </div>
            </div>
            <div class="flex gap-2 flex-shrink-0 self-start">
                <a href="{{ route('reports.beneficiary.pdf', $beneficiary) }}" target="_blank" class="app-secondary-button app-hero-button">
                    <i class="ph ph-file-pdf" aria-hidden="true"></i>
                    {{ __('web_app.actions.download_pdf') }}
                </a>
                @can('update', $beneficiary)
                    <button type="button" wire:click="openBeneficiaryForm({{ $beneficiary->id }})" class="app-primary-button app-hero-button">
                        <i class="ph ph-pencil-simple" aria-hidden="true"></i>
                        {{ __('web_app.actions.edit') }}
                    </button>
                @endcan
                <a href="{{ route('app.beneficiaries') }}" wire:navigate class="app-secondary-button app-hero-button">
                    <i class="ph ph-arrow-right" aria-hidden="true"></i>
                    {{ __('web_app.actions.beneficiaries') }}
                </a>
            </div>
        </div>
    </div>

    {{-- Quick Stat Cards --}}
    <div class="app-stat-grid app-stat-grid-2x2">
        <div class="app-stat-card tone-blue">
            <div class="app-stat-icon"><i class="ph ph-cake" aria-hidden="true"></i></div>
            <div>
                <p>{{ __('web_app.table.birth_date') }}</p>
                <strong>{{ $beneficiary->birth_date?->format('Y-m-d') ?? '—' }}</strong>
            </div>
        </div>
        <div class="app-stat-card tone-emerald">
            <div class="app-stat-icon"><i class="ph ph-gender-intersex" aria-hidden="true"></i></div>
            <div>
                <p>{{ __('web_app.table.gender') }}</p>
                <strong>{{ $beneficiary->gender ? __("beneficiaries.{$beneficiary->gender}") : '—' }}</strong>
            </div>
        </div>
        <div class="app-stat-card tone-amber">
            <div class="app-stat-icon"><i class="ph ph-map-pin-area" aria-hidden="true"></i></div>
            <div>
                <p>{{ __('web_app.table.area') }}</p>
                <strong title="{{ $beneficiary->area }}">{{ $beneficiary->area ?: '—' }}</strong>
            </div>
        </div>
        <div class="app-stat-card tone-rose">
            <div class="app-stat-icon"><i class="ph ph-heartbeat" aria-hidden="true"></i></div>
            <div>
                <p>{{ __('web_app.table.health') }}</p>
                <span class="app-status-pill mt-1 app-badge-health-{{ $beneficiary->health_status ?? 'good' }}">
                    {{ $beneficiary->health_status ? __("visits.{$beneficiary->health_status}") : __('visits.good') }}
                </span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column: Details --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Personal & Contact --}}
            <section class="app-panel">
                <div class="app-panel-header">
                    <div>
                        <p class="app-section-label">{{ __('web_app.table.contact_title') }}</p>
                        <h3>{{ __('web_app.table.contact_info') }}</h3>
                    </div>
                </div>
                <div class="p-4 sm:p-6">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5 text-sm">
                        <div>
                            <dt class="text-xs app-text-muted font-bold">{{ __('web_app.table.phone') }}</dt>
                            <dd class="mt-0.5 font-bold">{{ $beneficiary->phone ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs app-text-muted font-bold">WhatsApp</dt>
                            <dd class="mt-0.5 font-bold">{{ $beneficiary->whatsapp ?: '—' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs app-text-muted font-bold">{{ __('web_app.table.address') }}</dt>
                            <dd class="mt-0.5 break-words">{{ $beneficiary->address_text ?: '—' }}</dd>
                            @if ($beneficiary->area || $beneficiary->governorate)
                                <dd class="text-xs app-text-muted mt-1">{{ collect([$beneficiary->area, $beneficiary->governorate])->filter()->join(', ') }}</dd>
                            @endif
                            @if ($beneficiary->google_maps_url)
                                <dd class="mt-1.5">
                                    <a href="{{ $beneficiary->google_maps_url }}" target="_blank" class="app-link-inline">
                                        <i class="ph ph-map-pin" aria-hidden="true"></i> {{ __('web_app.table.view_location') }}
                                    </a>
                                </dd>
                            @endif
                        </div>
                        @if ($beneficiary->facebook_url || $beneficiary->instagram_url)
                            <div class="sm:col-span-2 flex gap-4">
                                @if ($beneficiary->facebook_url)
                                    <a href="{{ $beneficiary->facebook_url }}" target="_blank" class="app-link-inline">
                                        <i class="ph ph-facebook-logo" aria-hidden="true"></i> Facebook
                                    </a>
                                @endif
                                @if ($beneficiary->instagram_url)
                                    <a href="{{ $beneficiary->instagram_url }}" target="_blank" class="app-link-inline">
                                        <i class="ph ph-instagram-logo" aria-hidden="true"></i> Instagram
                                    </a>
                                @endif
                            </div>
                        @endif
                    </dl>
                </div>
            </section>

            {{-- Guardian & Family --}}
            <section class="app-panel">
                <div class="app-panel-header">
                    <div>
                        <p class="app-section-label">{{ __('web_app.table.guardian_title') }}</p>
                        <h3>{{ __('web_app.table.family_info') }}</h3>
                    </div>
                </div>
                <div class="p-4 sm:p-6">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5 text-sm">
                        <div>
                            <dt class="text-xs app-text-muted font-bold">{{ __('web_app.table.guardian') }}</dt>
                            <dd class="mt-0.5 font-bold">{{ $beneficiary->guardian_name ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs app-text-muted font-bold">{{ __('web_app.table.guardian_phone') }}</dt>
                            <dd class="mt-0.5 font-bold">{{ $beneficiary->guardian_phone ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs app-text-muted font-bold">{{ __('web_app.table.guardian_relation') }}</dt>
                            <dd class="mt-0.5">{{ $beneficiary->guardian_relation ?: '—' }}</dd>
                        </div>
                        <div></div>
                        <div>
                            <dt class="text-xs app-text-muted font-bold">{{ __('web_app.table.father_status') }}</dt>
                            <dd class="mt-0.5">{{ $beneficiary->father_status ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs app-text-muted font-bold">{{ __('web_app.table.mother_status') }}</dt>
                            <dd class="mt-0.5">{{ $beneficiary->mother_status ?: '—' }}</dd>
                        </div>
                        @if ($beneficiary->siblings_count !== null)
                            <div>
                                <dt class="text-xs app-text-muted font-bold">{{ __('web_app.table.siblings') }}</dt>
                                <dd class="mt-0.5 font-bold">{{ $beneficiary->siblings_count }}{{ $beneficiary->siblings_note ? " ({$beneficiary->siblings_note})" : '' }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </section>

            {{-- Medical Info --}}
            @if ($beneficiary->disability_type || $beneficiary->health_status || $beneficiary->doctor_name || $beneficiary->medical_notes)
            <section class="app-panel">
                <div class="app-panel-header">
                    <div>
                        <p class="app-section-label">{{ __('web_app.table.medical_title') }}</p>
                        <h3>{{ __('web_app.table.health_records') }}</h3>
                    </div>
                </div>
                <div class="p-4 sm:p-6">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5 text-sm">
                        @if ($beneficiary->disability_type)
                            <div>
                                <dt class="text-xs app-text-muted font-bold">{{ __('web_app.table.disability') }}</dt>
                                <dd class="mt-0.5 font-bold">{{ $beneficiary->disability_type }}{{ $beneficiary->disability_degree ? " ({$beneficiary->disability_degree})" : '' }}</dd>
                            </div>
                        @endif
                        @if ($beneficiary->doctor_name)
                            <div>
                                <dt class="text-xs app-text-muted font-bold">{{ __('web_app.table.doctor') }}</dt>
                                <dd class="mt-0.5">{{ $beneficiary->doctor_name }}</dd>
                            </div>
                        @endif
                        @if ($beneficiary->hospital_name)
                            <div>
                                <dt class="text-xs app-text-muted font-bold">{{ __('web_app.table.hospital') }}</dt>
                                <dd class="mt-0.5">{{ $beneficiary->hospital_name }}</dd>
                            </div>
                        @endif
                        @if ($beneficiary->last_medical_update)
                            <div>
                                <dt class="text-xs app-text-muted font-bold">{{ __('web_app.table.last_update') }}</dt>
                                <dd class="mt-0.5">{{ $beneficiary->last_medical_update->format('Y-m-d') }}</dd>
                            </div>
                        @endif
                        @if ($beneficiary->medical_notes)
                            <div class="sm:col-span-2">
                                <dt class="text-xs app-text-muted font-bold">{{ __('web_app.table.medical_notes') }}</dt>
                                <dd class="mt-0.5 whitespace-pre-wrap">{{ $beneficiary->medical_notes }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </section>
            @endif

            {{-- Active Medications --}}
            @if ($beneficiary->activeMedications->isNotEmpty())
            <section class="app-panel">
                <div class="app-panel-header">
                    <div>
                        <p class="app-section-label">{{ __('web_app.table.medications') }}</p>
                        <h3>{{ __('web_app.table.medication_list') }}</h3>
                    </div>
                </div>
                <div class="p-4 sm:p-6">
                    <div class="space-y-3">
                        @foreach ($beneficiary->activeMedications as $medication)
                            <div class="flex items-center justify-between py-2 border-b last:border-0" style="border-color: var(--clr-silver-mist)">
                                <div>
                                    <p class="font-bold text-sm">{{ $medication->name }}</p>
                                    @if ($medication->dosage)
                                        <p class="text-xs app-text-muted">{{ $medication->dosage }}</p>
                                    @endif
                                </div>
                                <span class="text-xs app-text-muted">{{ $medication->created_at->format('Y-m-d') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
            @endif
        </div>

        {{-- Right Column: Sidebar --}}
        <div class="space-y-6">

            {{-- Recent Visits --}}
            <section class="app-panel">
                <div class="app-panel-header">
                    <div>
                        <p class="app-section-label">{{ __('web_app.table.activity') }}</p>
                        <h3>{{ __('web_app.table.visits') }}</h3>
                    </div>
                    <a href="{{ route('app.visits') }}" wire:navigate>{{ __('web_app.actions.view_all') }}</a>
                </div>
                <div class="p-4 sm:p-6">
                    @forelse ($beneficiary->visits as $visit)
                        <article class="flex items-start gap-3 py-3 border-b last:border-0" style="border-color: var(--clr-silver-mist)">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0" style="background: var(--clr-soft-blue, #dbeafe)">
                                <i class="ph ph-clipboard-text" style="color: var(--clr-calm-blue)" aria-hidden="true"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold">{{ $visit->createdBy?->name ?? __('web_app.dashboard.unknown_user') }}</p>
                                <p class="text-xs app-text-muted">
                                    {{ $visit->visit_date?->format('Y-m-d') }}
                                    @if ($visit->type)
                                        &middot; {{ __("visits.{$visit->type}") }}
                                    @endif
                                </p>
                            </div>
                        </article>
                    @empty
                        <div class="app-empty-state app-empty-state-compact">
                            <i class="ph ph-clipboard-text" aria-hidden="true"></i>
                            <p>{{ __('web_app.dashboard.empty_visits') }}</p>
                        </div>
                    @endforelse
                </div>
            </section>

            {{-- Financial Status --}}
            @if ($beneficiary->financial_status)
            <section class="app-panel">
                <div class="app-panel-header">
                    <div>
                        <p class="app-section-label">{{ __('web_app.table.financial') }}</p>
                        <h3>{{ __('web_app.table.financial_status') }}</h3>
                    </div>
                </div>
                <div class="p-4 sm:p-6 text-sm">
                    <p class="font-bold">{{ $beneficiary->financial_status ? __("beneficiaries.{$beneficiary->financial_status}") : '—' }}</p>
                    @if ($beneficiary->financial_notes)
                        <p class="mt-2 app-text-muted">{{ $beneficiary->financial_notes }}</p>
                    @endif
                </div>
            </section>
            @endif

            {{-- Prayer Requests --}}
            @if ($beneficiary->prayerRequests->isNotEmpty())
            <section class="app-panel">
                <div class="app-panel-header">
                    <div>
                        <p class="app-section-label">{{ __('web_app.table.prayers') }}</p>
                        <h3>{{ __('web_app.table.prayer_list') }}</h3>
                    </div>
                    <a href="{{ route('app.prayer-requests') }}" wire:navigate>{{ __('web_app.actions.view_all') }}</a>
                </div>
                <div class="p-4 sm:p-6">
                    @foreach ($beneficiary->prayerRequests as $prayer)
                        <article class="flex items-start gap-3 py-3 border-b last:border-0" style="border-color: var(--clr-silver-mist)">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0" style="background: var(--clr-soft-amber, #fef3c7)">
                                <i class="ph ph-hands-praying" style="color: var(--clr-warm-gold)" aria-hidden="true"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold">{{ $prayer->title }}</p>
                                @if ($prayer->body)
                                    <p class="text-xs app-text-muted mt-0.5 line-clamp-2">{{ $prayer->body }}</p>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
            @endif
        </div>
    </div>

    @include('livewire.web-app.partials.modals.beneficiary-form')
</section>
