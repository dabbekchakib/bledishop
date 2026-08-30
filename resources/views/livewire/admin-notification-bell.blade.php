<div
    wire:poll.15s="refresh"
    class="bledi-bell"
    x-data="{ open: false }"
    @keydown.escape.window="open = false"
    @click.outside="open = false"
>
    <button
        type="button"
        x-on:click="open = ! open"
        class="bledi-bell__trigger"
        aria-haspopup="true"
        :aria-expanded="open.toString()"
        aria-label="{{ __('admin.notifications.page_title') }}"
        title="{{ __('admin.notifications.page_title') }}"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
        </svg>

        @if ($unreadCount > 0)
            <span class="bledi-bell__badge">{{ $this->badgeText() }}</span>
        @endif
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
        class="bledi-bell__menu"
    >
        <div class="bledi-bell__header">
            <span class="bledi-bell__title">{{ __('admin.notifications.page_title') }}</span>

            @if ($unreadCount > 0)
                <button type="button" wire:click="markAllRead" class="bledi-bell__mark-all">
                    {{ __('admin.notifications.mark_all_read') }}
                </button>
            @endif
        </div>

        <div class="bledi-bell__list">
            @forelse ($recent as $item)
                @php
                    $color = match ($item['priority']) {
                        'danger' => '#ef4444',
                        'warning' => '#f59e0b',
                        'success' => '#22c55e',
                        default => '#3b82f6',
                    };
                @endphp
                <button
                    type="button"
                    wire:click="open('{{ $item['id'] }}')"
                    class="bledi-bell__item {{ $item['read'] ? 'bledi-bell__item--read' : '' }}"
                >
                    <span class="bledi-bell__dot" style="background-color: {{ $color }};"></span>
                    <span class="bledi-bell__text">
                        <span class="bledi-bell__msg">{{ $item['message'] }}</span>
                        <span class="bledi-bell__time">{{ $item['time'] }}</span>
                    </span>
                </button>
            @empty
                <div class="bledi-bell__empty">{{ __('admin.notifications.empty') }}</div>
            @endforelse
        </div>

        <a href="{{ url('/admin/notifications') }}" class="bledi-bell__footer">
            {{ __('admin.notifications.view_all') }}
        </a>
    </div>
</div>

<style>
    .bledi-bell { position: relative; }
    .bledi-bell__trigger {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.5rem;
        border-radius: 0.5rem;
        color: var(--gray-700);
        background: transparent;
        transition: background-color 150ms ease, color 150ms ease;
    }
    .dark .bledi-bell__trigger { color: var(--gray-200); }
    .bledi-bell__trigger:hover { background: var(--gray-100); color: var(--gray-950); }
    .dark .bledi-bell__trigger:hover { background: rgb(255 255 255 / 0.08); color: var(--gray-100); }
    .bledi-bell__badge {
        position: absolute;
        top: 0.15rem;
        inset-inline-end: 0.1rem;
        min-width: 1.05rem;
        height: 1.05rem;
        border-radius: 9999px;
        background: var(--primary-500);
        color: #fff;
        font-size: 0.6rem;
        font-weight: 700;
        line-height: 1.05rem;
        padding-inline: 0.25rem;
        text-align: center;
    }
    .bledi-bell__menu {
        position: absolute;
        inset-inline-end: 0;
        top: calc(100% + 0.35rem);
        z-index: 60;
        width: 20rem;
        max-width: 85vw;
        border-radius: 0.75rem;
        border: 1px solid var(--gray-200);
        background: var(--gray-50);
        box-shadow: var(--shadow-lg);
        overflow: hidden;
    }
    .dark .bledi-bell__menu { border-color: rgb(255 255 255 / 0.12); background: var(--gray-800); }
    .bledi-bell__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        padding: 0.6rem 0.75rem;
        border-bottom: 1px solid var(--gray-200);
    }
    .dark .bledi-bell__header { border-color: rgb(255 255 255 / 0.1); }
    .bledi-bell__title { font-weight: 600; font-size: 0.85rem; color: var(--gray-900); }
    .dark .bledi-bell__title { color: var(--gray-100); }
    .bledi-bell__mark-all { font-size: 0.72rem; color: var(--primary-600); font-weight: 500; }
    .dark .bledi-bell__mark-all { color: var(--primary-400); }
    .bledi-bell__list { max-height: 22rem; overflow-y: auto; }
    .bledi-bell__item {
        display: flex;
        width: 100%;
        align-items: flex-start;
        gap: 0.6rem;
        padding: 0.6rem 0.75rem;
        text-align: start;
        border-bottom: 1px solid var(--gray-100);
    }
    .dark .bledi-bell__item { border-color: rgb(255 255 255 / 0.06); }
    .bledi-bell__item:hover { background: var(--gray-100); }
    .dark .bledi-bell__item:hover { background: rgb(255 255 255 / 0.06); }
    .bledi-bell__item--read { opacity: 0.6; }
    .bledi-bell__dot { width: 0.5rem; height: 0.5rem; border-radius: 9999px; margin-top: 0.25rem; flex-shrink: 0; }
    .bledi-bell__text { display: flex; flex-direction: column; gap: 0.05rem; min-width: 0; }
    .bledi-bell__msg { font-size: 0.78rem; color: var(--gray-800); line-height: 1.25; }
    .dark .bledi-bell__msg { color: var(--gray-200); }
    .bledi-bell__time { font-size: 0.68rem; color: var(--gray-400); }
    .bledi-bell__empty { padding: 1.5rem; text-align: center; font-size: 0.8rem; color: var(--gray-500); }
    .bledi-bell__footer {
        display: block;
        padding: 0.6rem 0.75rem;
        text-align: center;
        font-size: 0.78rem;
        font-weight: 500;
        color: var(--primary-600);
        border-top: 1px solid var(--gray-200);
    }
    .dark .bledi-bell__footer { color: var(--primary-400); border-color: rgb(255 255 255 / 0.1); }
    .bledi-bell__footer:hover { background: var(--gray-100); }
    .dark .bledi-bell__footer:hover { background: rgb(255 255 255 / 0.06); }
</style>
