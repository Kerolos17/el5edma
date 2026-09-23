@props([
    'beneficiary',
    'href'      => null,
    'showArrow' => true,
])

@php
    $statusMap = [
        'active'   => 'badge-success',
        'inactive' => 'badge-info',
    ];
    $statusClass = $statusMap[$beneficiary->status] ?? 'badge-warning';

    $statusLabels = [
        'active'   => __('beneficiaries.active'),
        'inactive' => __('beneficiaries.inactive'),
    ];
    $statusLabel = $statusLabels[$beneficiary->status] ?? ($beneficiary->status ?? '');
@endphp

<div class="s-card card-lift rounded-2xl px-4 py-3 flex items-center gap-3"
   {{ $attributes }}>

    {{-- Main link (avatar + info) --}}
    <a href="{{ $href ?? '#' }}" wire:navigate
       class="flex items-center gap-3 flex-1 min-w-0"
       aria-label="{{ $beneficiary->full_name }}">

        {{-- Avatar --}}
        <x-ui.avatar
            :name="$beneficiary->full_name"
            :src="$beneficiary->photo_url ?? null"
            size="md"
            shape="square"
        />

        {{-- Info --}}
        <span class="flex-1 min-w-0 block">
            <span class="flex items-center gap-2 flex-wrap">
                <span class="font-bold text-teal-900 text-sm truncate block">{{ $beneficiary->full_name }}</span>
                @if($beneficiary->status)
                    <span class="badge-pill {{ $statusClass }} text-xs px-2 py-0.5">
                        {{ $statusLabel }}
                    </span>
                @endif
            </span>
            <span class="text-xs text-gray-500 mt-0.5 block">
                {{ $beneficiary->code }}
                @if($beneficiary->area)
                    · {{ $beneficiary->area }}
                @endif
            </span>
        </span>
    </a>

    {{-- WhatsApp: standalone 44px button, never nested inside the link --}}
    @if($beneficiary->whatsapp_url ?? false)
        <a href="{{ $beneficiary->whatsapp_url }}" target="_blank" rel="noopener"
           class="btn-whatsapp flex-shrink-0"
           aria-label="واتساب {{ $beneficiary->full_name }}">
            <i class="ph-fill ph-whatsapp-logo text-lg" aria-hidden="true"></i>
        </a>
    @endif

    @if($showArrow)
        <i class="ph ph-caret-left text-gray-300 text-sm flex-shrink-0 rtl:inline ltr:hidden" aria-hidden="true"></i>
        <i class="ph ph-caret-right text-gray-300 text-sm flex-shrink-0 rtl:hidden ltr:inline" aria-hidden="true"></i>
    @endif
</div>
