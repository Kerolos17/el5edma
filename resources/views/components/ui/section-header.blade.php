@props([
    'icon'    => 'ph-fill ph-info',
    'title'   => '',
    'color'   => 'teal',  // teal | red | gold
])

@php
    $badgeClasses = [
        'teal' => 'section-header__badge--teal',
        'red'  => 'section-header__badge--red',
        'gold' => 'section-header__badge--gold',
    ];
    $badge = $badgeClasses[$color] ?? $badgeClasses['teal'];
@endphp

<div {{ $attributes->class(['flex items-center gap-2']) }}>
    <div class="section-header__badge {{ $badge }}">
        <i class="{{ $icon }} text-sm section-header__icon"></i>
    </div>
    <h2 class="font-bold text-teal-900">{{ $title }}</h2>
    @if(isset($action))
        <div class="ms-auto">{{ $action }}</div>
    @endif
</div>
