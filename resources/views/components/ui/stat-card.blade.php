@props([
    'label'    => '',
    'value'    => 0,
    'icon'     => 'ph-fill ph-star',
    'gradient' => 'teal',    // teal | gold | teal-light | critical | success
    'pulse'    => false,     // أضف تنبيه نبض للحالات الحرجة
])

@php
    $gradients = [
        'teal'       => 'linear-gradient(135deg, #006D77 0%, #003942 100%)',
        'gold'       => 'linear-gradient(135deg, #F4A261 0%, #D68A3D 100%)',
        'teal-light' => 'linear-gradient(135deg, #4D9BA3 0%, #006D77 100%)',
        'critical'   => 'linear-gradient(135deg, #E63946 0%, #C2323E 100%)',
        'success'    => 'linear-gradient(135deg, #06A77D 0%, #048C68 100%)',
    ];
    $bg = $gradients[$gradient] ?? $gradients['teal'];
@endphp

<div class="s-card stat-card relative overflow-hidden rounded-3xl p-5 text-white {{ $pulse ? 'critical-indicator' : '' }}"
     style="background: {{ $bg }};">
    <p class="text-white/70 text-xs font-semibold mb-2">{{ $label }}</p>
    <p class="text-4xl font-bold" style="font-family: var(--font-accent);">{{ $value }}</p>
    <i class="{{ $icon }} absolute left-4 bottom-4 text-white/15 text-5xl"></i>
</div>
