@php
    $variables = app(\App\Services\ThemeService::class)->cssVariables();
@endphp

@if (filled($variables))
<style>
    :root {
        @foreach ($variables as $name => $value)
            {{ $name }}: {{ $value }};
        @endforeach
    }

    [data-theme="dark"] {
        --color-background: var(--color-dark-background);
        --color-surface: var(--color-dark-surface);
        --color-text: var(--color-dark-text);
        --color-text-muted: var(--color-dark-text-muted);
        --color-border: var(--color-dark-border);
        --color-heading: var(--color-dark-heading);
    }
</style>
@endif