@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => '',
    'required' => false,
    'error' => null,
    'autocomplete' => null,
    'rows' => null,
    'placeholder' => null,
])

@php
    $classes = 'w-full rounded-xl border bg-background px-3.5 py-2.5 text-sm text-text shadow-sm transition-colors placeholder:text-text-muted focus:outline-none focus:ring-2 ' .
        ($error
            ? 'border-danger focus:border-danger focus:ring-danger/30'
            : 'border-border focus:border-primary focus:ring-primary/30');
@endphp

<div>
    @if ($label)
        <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-text">
            {{ $label }}{{ $required ? '' : ' ' }}
        </label>
    @endif

    @if ($type === 'textarea')
        <textarea
            id="{{ $name }}"
            name="{{ $name }}"
            rows="{{ $rows ?? 3 }}"
            @required($required)
            placeholder="{{ $placeholder }}"
            class="{{ $classes }}"
        >{{ $value }}</textarea>
    @else
        <input
            id="{{ $name }}"
            type="{{ $type }}"
            name="{{ $name }}"
            value="{{ $type === 'password' ? '' : $value }}"
            autocomplete="{{ $autocomplete }}"
            @required($required)
            placeholder="{{ $placeholder }}"
            class="{{ $classes }}"
            {{ $attributes }}
        >
    @endif

    @if ($error)
        <p class="mt-1.5 text-xs text-danger">{{ $error }}</p>
    @endif
</div>
