@props([
    'label'    => '',
    'value'    => 0,
    'icon'     => 'ph-fill ph-star',
    'gradient' => 'teal',    // teal | gold | teal-light | critical | success
    'pulse'    => false,     // أضف تنبيه نبض للحالات الحرجة
])

@php
    $surfaceClasses = [
        'teal'       => 'stat-card--teal',
        'gold'       => 'stat-card--gold',
        'teal-light' => 'stat-card--teal-light',
        'critical'   => 'stat-card--critical',
        'success'    => 'stat-card--success',
    ];
    $surface = $surfaceClasses[$gradient] ?? $surfaceClasses['teal'];
@endphp

<div class="s-card stat-card relative overflow-hidden rounded-3xl p-5 text-white {{ $surface }} {{ $pulse ? 'critical-indicator' : '' }}">
    <p class="text-white/85 text-xs font-semibold mb-2">{{ $label }}</p>
    <p class="stat-card__value text-4xl font-bold">{{ $value }}</p>
    <i class="{{ $icon }} absolute start-4 bottom-4 text-white/15 text-5xl"></i>
</div>
