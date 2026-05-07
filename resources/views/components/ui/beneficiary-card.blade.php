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
        'active'   => 'نشط',
        'inactive' => 'غير نشط',
    ];
    $statusLabel = $statusLabels[$beneficiary->status] ?? ($beneficiary->status ?? '');
@endphp

<a href="{{ $href ?? '#' }}" wire:navigate
   class="s-card card-lift rounded-2xl px-4 py-3 flex items-center gap-3 block"
   {{ $attributes }}>

    {{-- Avatar --}}
    <x-ui.avatar
        :name="$beneficiary->full_name"
        :src="$beneficiary->photo_url ?? null"
        size="md"
        shape="square"
    />

    {{-- Info --}}
    <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 flex-wrap">
            <p class="font-bold text-teal-900 text-sm truncate">{{ $beneficiary->full_name }}</p>
            @if($beneficiary->status)
                <span class="badge-pill {{ $statusClass }} text-xs px-2 py-0.5">
                    {{ $statusLabel }}
                </span>
            @endif
        </div>
        <p class="text-xs text-gray-400 mt-0.5">
            {{ $beneficiary->code }}
            @if($beneficiary->area)
                · {{ $beneficiary->area }}
            @endif
        </p>
    </div>

    {{-- WhatsApp --}}
    @if($beneficiary->whatsapp_url ?? false)
        <span onclick="event.preventDefault(); window.open('{{ $beneficiary->whatsapp_url }}', '_blank')"
              class="btn-whatsapp flex-shrink-0" aria-label="واتساب">
            <i class="ph-fill ph-whatsapp-logo text-lg"></i>
        </span>
    @endif

    @if($showArrow)
        <i class="ph ph-caret-left text-gray-300 text-sm flex-shrink-0"></i>
    @endif
</a>
