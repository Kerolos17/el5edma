@props([
    'name'    => '',
    'src'     => null,
    'size'    => 'md',    // xs | sm | md | lg | xl
    'shape'   => 'round', // round | square
    'gradient'=> 'teal',  // teal | gold
])

@php
    $sizes = [
        'xs' => ['outer' => 'w-7 h-7',   'text' => 'text-xs'],
        'sm' => ['outer' => 'w-9 h-9',   'text' => 'text-sm'],
        'md' => ['outer' => 'w-11 h-11', 'text' => 'text-base'],
        'lg' => ['outer' => 'w-14 h-14', 'text' => 'text-xl'],
        'xl' => ['outer' => 'w-16 h-16', 'text' => 'text-2xl'],
    ];
    $shapes = [
        'round'  => 'rounded-full',
        'square' => 'rounded-2xl',
    ];
    $surfaces = [
        'teal' => 'avatar__surface--teal',
        'gold' => 'avatar__surface--gold',
    ];

    $s = $sizes[$size]     ?? $sizes['md'];
    $r = $shapes[$shape]   ?? $shapes['round'];
    $g = $surfaces[$gradient] ?? $surfaces['teal'];

    $initial = $name ? mb_substr($name, 0, 1) : '؟';
@endphp

<div {{ $attributes->class([$s['outer'], $r, 'overflow-hidden flex-shrink-0']) }}>
    @if($src)
        <img src="{{ $src }}" alt="{{ $name }}" class="w-full h-full object-cover" loading="lazy" decoding="async" width="96" height="96"
             onerror="this.classList.add('hidden'); this.nextElementSibling.classList.add('avatar__fallback--show');">
        <div class="avatar__fallback {{ $g }} {{ $s['text'] }} text-teal-800">
            {{ $initial }}
        </div>
    @else
        <div class="w-full h-full flex items-center justify-center {{ $s['text'] }} font-bold {{ $g }} text-teal-800">
            {{ $initial }}
        </div>
    @endif
</div>
