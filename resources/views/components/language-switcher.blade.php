<nav aria-label="{{ __('messages.language') }}" class="flex items-center gap-1">
    @foreach ($locales as $code => $label)
        @if ($code === $current)
            <span class="inline-flex items-center rounded-md bg-primary px-2.5 py-1 text-xs font-semibold text-white">
                {{ $label }}
            </span>
        @else
            <a href="{{ $links[$code] }}"
               class="inline-flex items-center rounded-md border border-border px-2.5 py-1 text-xs font-medium text-text-muted transition-colors hover:bg-surface hover:text-text">
                {{ $label }}
            </a>
        @endif
    @endforeach
</nav>