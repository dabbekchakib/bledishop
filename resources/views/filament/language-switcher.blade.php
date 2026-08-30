@php
    $localization = app(\App\Services\LocalizationService::class);
    $current = $localization->isAvailable(app()->getLocale())
        ? app()->getLocale()
        : $localization->defaultLocale();
    $locales = collect($localization->availableLocales())
        ->map(fn (string $code): array => [
            'code' => $code,
            'label' => $localization->localeLabel($code) ?? strtoupper($code),
        ])
        ->values()
        ->all();
@endphp

<style>
    .bledi-admin-locale-switcher { position: relative; }
    .bledi-admin-locale-switcher__trigger {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.5rem 0.6rem;
        border-radius: 0.5rem;
        font-weight: 500;
        font-size: 0.875rem;
        line-height: 1.25rem;
        color: var(--gray-700);
        background: transparent;
        transition: background-color 150ms ease, color 150ms ease;
    }
    .dark .bledi-admin-locale-switcher__trigger { color: var(--gray-200); }
    .bledi-admin-locale-switcher__trigger:hover { background: var(--gray-100); color: var(--gray-950); }
    .dark .bledi-admin-locale-switcher__trigger:hover { background: rgb(255 255 255 / 0.08); color: var(--gray-100); }
    .bledi-admin-locale-switcher__menu {
        position: absolute;
        inset-inline-end: 0;
        top: calc(100% + 0.25rem);
        z-index: 60;
        min-width: 11rem;
        padding: 0.35rem;
        border-radius: 0.75rem;
        border: 1px solid var(--gray-200);
        background: var(--gray-50);
        box-shadow: var(--shadow-lg);
    }
    .dark .bledi-admin-locale-switcher__menu { border-color: rgb(255 255 255 / 0.12); background: var(--gray-800); }
    .bledi-admin-locale-switcher__item {
        display: flex;
        width: 100%;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        line-height: 1.25rem;
        color: var(--gray-700);
        background: transparent;
        transition: background-color 150ms ease, color 150ms ease;
    }
    .dark .bledi-admin-locale-switcher__item { color: var(--gray-200); }
    .bledi-admin-locale-switcher__item:hover { background: var(--gray-100); color: var(--gray-950); }
    .dark .bledi-admin-locale-switcher__item:hover { background: rgb(255 255 255 / 0.08); color: var(--gray-100); }
    .bledi-admin-locale-switcher__item--active { background: var(--gray-100); font-weight: 600; color: var(--gray-950); }
    .dark .bledi-admin-locale-switcher__item--active { background: rgb(255 255 255 / 0.1); color: var(--gray-100); }
    .bledi-admin-locale-switcher__chevron { transition: transform 150ms ease; }
    .bledi-admin-locale-switcher__chevron--open { transform: rotate(180deg); }
</style>

<div
    x-data="{ open: false }"
    @keydown.escape.window="open = false"
    @click.outside="open = false"
    class="bledi-admin-locale-switcher"
>
    <button
        type="button"
        x-on:click="open = ! open"
        class="bledi-admin-locale-switcher__trigger"
        aria-haspopup="true"
        :aria-expanded="open.toString()"
        aria-label="{{ __('admin.language') }}"
        title="{{ __('admin.language') }}"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z"/><path d="M3.6 9h16.8M3.6 15h16.8"/><path d="M12 3a15.3 15.3 0 0 1 4 9 15.3 15.3 0 0 1-4 9 15.3 15.3 0 0 1-4-9 15.3 15.3 0 0 1 4-9z"/>
        </svg>
        <span style="font-size: 0.625rem; font-weight: 700; text-transform: uppercase;">{{ $current }}</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" class="bledi-admin-locale-switcher__chevron" :class="open && 'bledi-admin-locale-switcher__chevron--open'">
            <path d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
        </svg>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition:enter="fi-transition fi-ease-out fi-duration-150"
        x-transition:enter-start="fi-opacity-0 fi-scale-95"
        x-transition:enter-end="fi-opacity-100 fi-scale-100"
        x-transition:leave="fi-transition fi-ease-in fi-duration-100"
        x-transition:leave-start="fi-opacity-100 fi-scale-100"
        x-transition:leave-end="fi-opacity-0 fi-scale-95"
        class="bledi-admin-locale-switcher__menu"
    >
        @foreach ($locales as $item)
            @if ($item['code'] === $current)
                <span class="bledi-admin-locale-switcher__item bledi-admin-locale-switcher__item--active">
                    {{ $item['label'] }}
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                </span>
            @else
                <form method="POST" action="{{ route('admin.locale.switch', ['locale' => $item['code']]) }}">
                    @csrf
                    <button type="submit" class="bledi-admin-locale-switcher__item">
                        {{ $item['label'] }}
                    </button>
                </form>
            @endif
        @endforeach
    </div>
</div>