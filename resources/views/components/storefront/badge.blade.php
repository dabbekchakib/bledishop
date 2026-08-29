@props([
    'color' => 'primary',
    'class' => '',
])

@php
    $colors = [
        'primary' => 'bg-primary text-white',
        'danger' => 'bg-danger text-white',
        'success' => 'bg-success text-white',
        'warning' => 'bg-warning text-white',
        'dark' => 'bg-heading text-white',
        'muted' => 'bg-surface text-text-muted',
    ];
    $colorClass = $colors[$color] ?? $colors['primary'];
@endphp

<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $colorClass }} {{ $class }}">
    {{ $slot }}
</span>
