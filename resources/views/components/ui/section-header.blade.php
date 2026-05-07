@props([
    'icon'    => 'ph-fill ph-info',
    'title'   => '',
    'color'   => 'teal',  // teal | red | gold
])

@php
    $colors = [
        'teal' => ['bg' => 'rgba(0,109,119,0.1)',   'text' => '#006D77'],
        'red'  => ['bg' => 'rgba(230,57,70,0.12)',  'text' => '#E63946'],
        'gold' => ['bg' => 'rgba(244,162,97,0.15)', 'text' => '#D68A3D'],
    ];
    $c = $colors[$color] ?? $colors['teal'];
@endphp

<div {{ $attributes->class(['flex items-center gap-2']) }}>
    <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0"
         style="background: {{ $c['bg'] }}">
        <i class="{{ $icon }} text-sm" style="color: {{ $c['text'] }}"></i>
    </div>
    <h2 class="font-bold text-teal-900">{{ $title }}</h2>
    @if(isset($action))
        <div class="mr-auto">{{ $action }}</div>
    @endif
</div>
