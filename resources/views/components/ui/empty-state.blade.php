@props([
    'icon'       => 'ph-smiley-sad',
    'message'    => 'لا توجد بيانات',
    'size'       => 'md',   // sm | md | lg
])

@php
    $sizes = [
        'sm' => ['wrap' => 'p-6',  'icon' => 'text-3xl', 'msg' => 'text-sm'],
        'md' => ['wrap' => 'p-8',  'icon' => 'text-4xl', 'msg' => 'text-sm'],
        'lg' => ['wrap' => 'p-10', 'icon' => 'text-5xl', 'msg' => 'text-base'],
    ];
    $s = $sizes[$size] ?? $sizes['md'];
@endphp

<div {{ $attributes->class(['s-card rounded-3xl text-center', $s['wrap']]) }}>
    <i class="ph {{ $icon }} {{ $s['icon'] }} text-gray-300 mb-3 block"></i>
    <p class="{{ $s['msg'] }} text-gray-400">{{ $message }}</p>
    @if(isset($action))
        <div class="mt-3">{{ $action }}</div>
    @endif
</div>
