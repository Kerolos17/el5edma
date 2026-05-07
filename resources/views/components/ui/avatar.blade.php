@props([
    'name'    => '',
    'src'     => null,
    'size'    => 'md',    // xs | sm | md | lg | xl
    'shape'   => 'round', // round | square
    'gradient'=> 'teal',  // teal | gold
])

@php
    $sizes = [
        'xs' => ['outer' => 'w-7 h-7',   'text' => 'text-xs',   'font' => '700'],
        'sm' => ['outer' => 'w-9 h-9',   'text' => 'text-sm',   'font' => '700'],
        'md' => ['outer' => 'w-11 h-11', 'text' => 'text-base', 'font' => '700'],
        'lg' => ['outer' => 'w-14 h-14', 'text' => 'text-xl',   'font' => '700'],
        'xl' => ['outer' => 'w-16 h-16', 'text' => 'text-2xl',  'font' => '700'],
    ];
    $shapes = [
        'round'  => 'rounded-full',
        'square' => 'rounded-2xl',
    ];
    $gradients = [
        'teal' => 'linear-gradient(135deg, #C7E5E8, #4D9BA3)',
        'gold' => 'linear-gradient(135deg, #F7BB86, #F4A261)',
    ];

    $s = $sizes[$size]   ?? $sizes['md'];
    $r = $shapes[$shape] ?? $shapes['round'];
    $g = $gradients[$gradient] ?? $gradients['teal'];

    $initial = $name ? mb_substr($name, 0, 1) : '؟';
@endphp

<div {{ $attributes->class([$s['outer'], $r, 'overflow-hidden flex-shrink-0']) }}>
    @if($src)
        <img src="{{ $src }}" alt="{{ $name }}" class="w-full h-full object-cover" loading="lazy" decoding="async">
    @else
        <div class="w-full h-full flex items-center justify-center {{ $s['text'] }} font-bold text-teal-800"
             style="background: {{ $g }}; font-weight: {{ $s['font'] }};">
            {{ $initial }}
        </div>
    @endif
</div>
