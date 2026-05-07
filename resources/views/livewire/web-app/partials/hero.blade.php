<div class="app-hero-panel">
    <div>
        <p class="app-section-label">مساحة عمل حسب الدور</p>
        <h2>{{ $meta['title'] }}</h2>
        <p>{{ $meta['description'] }}</p>
    </div>

    <div class="app-hero-actions">
        @if ($this->section === 'visits')
            <button type="button" wire:click="openVisitForm" class="app-primary-button">
                <i class="ph ph-plus" aria-hidden="true"></i>
                تسجيل زيارة
            </button>
        @elseif ($this->section === 'scheduled-visits' && auth()->user()->can('create', \App\Models\ScheduledVisit::class))
            <button type="button" wire:click="openScheduledVisitForm" class="app-primary-button">
                <i class="ph ph-plus" aria-hidden="true"></i>
                جدولة زيارة
            </button>
        @elseif ($this->section === 'prayer-requests')
            <button type="button" wire:click="openPrayerForm" class="app-primary-button">
                <i class="ph ph-plus" aria-hidden="true"></i>
                إضافة طلب صلاة
            </button>
        @elseif ($this->section === 'beneficiaries' && auth()->user()->can('create', \App\Models\Beneficiary::class))
            <button type="button" wire:click="openBeneficiaryForm" class="app-primary-button">
                <i class="ph ph-user-plus" aria-hidden="true"></i>
                إضافة مخدوم
            </button>
        @elseif ($this->section === 'medical-files' && auth()->user()->can('create', \App\Models\MedicalFile::class))
            <button type="button" wire:click="openMedicalFileForm" class="app-primary-button">
                <i class="ph ph-upload-simple" aria-hidden="true"></i>
                رفع ملف طبي
            </button>
        @elseif ($this->section === 'users' && auth()->user()->can('create', \App\Models\User::class))
            <button type="button" wire:click="openUserForm" class="app-primary-button">
                <i class="ph ph-user-plus" aria-hidden="true"></i>
                إضافة مستخدم
            </button>
        @elseif ($this->section === 'service-groups' && auth()->user()->can('create', \App\Models\ServiceGroup::class))
            <button type="button" wire:click="openServiceGroupForm" class="app-primary-button">
                <i class="ph ph-plus" aria-hidden="true"></i>
                إضافة مجموعة
            </button>
        @else
            <a href="{{ $meta['primaryAction']['route'] }}" wire:navigate class="app-primary-button">
                <i class="ph {{ $meta['primaryAction']['icon'] }}" aria-hidden="true"></i>
                {{ $meta['primaryAction']['label'] }}
            </a>
        @endif

        <a href="{{ $meta['secondaryAction']['route'] }}" wire:navigate class="app-secondary-button">
            <i class="ph {{ $meta['secondaryAction']['icon'] }}" aria-hidden="true"></i>
            {{ $meta['secondaryAction']['label'] }}
        </a>
    </div>
</div>
