@props([
    'value' => 0,
    'compare' => null,
    'size' => 'base',
    'class' => '',
])

@php
    $sizes = [
        'sm' => 'text-sm',
        'base' => 'text-lg',
        'lg' => 'text-2xl',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['base'];
@endphp

@if (filled($compare) && (float) $compare > (float) $value)
    <div class="flex flex-wrap items-baseline gap-x-2">
        <span class="font-bold {{ $sizeClass }} {{ $class }}">{{ format_price($value) }}</span>
        <span class="text-sm text-text-muted line-through">{{ format_price($compare) }}</span>
    </div>
@else
    <span class="font-bold {{ $sizeClass }} {{ $class }}">{{ format_price($value) }}</span>
@endif
