<x-filament-panels::page>
    <div class="space-y-4">
        {{-- Actions --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    wire:click="filterBy('all')"
                    class="bledi-notif-tab {{ $state === 'all' ? 'bledi-notif-tab--active' : '' }}"
                >
                    {{ __('admin.notifications.all') }}
                </button>
                <button
                    type="button"
                    wire:click="filterBy('unread')"
                    class="bledi-notif-tab {{ $state === 'unread' ? 'bledi-notif-tab--active' : '' }}"
                >
                    {{ __('admin.notifications.unread') }}
                    <span class="bledi-notif-tab__badge">{{ $this->unreadCount }}</span>
                </button>
                <button
                    type="button"
                    wire:click="filterBy('read')"
                    class="bledi-notif-tab {{ $state === 'read' ? 'bledi-notif-tab--active' : '' }}"
                >
                    {{ __('admin.notifications.read') }}
                </button>
            </div>

            <button
                type="button"
                wire:click="markAllRead"
                @if($this->filteredUnreadCount === 0) disabled @endif
                class="bledi-notif-btn bledi-notif-btn--secondary"
            >
                {{ __('admin.notifications.mark_all_read') }}
            </button>
        </div>

        {{-- Filters --}}
        <div class="bledi-notif-filters">
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('admin.notifications.search_placeholder') }}"
                class="bledi-notif-input"
            >

            <select wire:model.live="type" wire:change="setType($event.target.value)" class="bledi-notif-input">
                <option value="">{{ __('admin.notifications.all_types') }}</option>
                @foreach ($this->typeOptions() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>

            <select wire:model.live="period" class="bledi-notif-input" wire:change="setPeriod($event.target.value)">
                <option value="all">{{ __('admin.notifications.period_all') }}</option>
                <option value="today">{{ __('admin.notifications.period_today') }}</option>
                <option value="7d">{{ __('admin.notifications.period_7d') }}</option>
                <option value="30d">{{ __('admin.notifications.period_30d') }}</option>
                <option value="month">{{ __('admin.notifications.period_month') }}</option>
            </select>
        </div>

        {{-- List --}}
        @if ($this->notifications->isEmpty())
            <div class="bledi-notif-empty">
                {{ __('admin.notifications.empty') }}
            </div>
        @else
            <div class="bledi-notif-list">
                @foreach ($this->notifications as $notification)
                    @php
                        $priority = $notification->data['priority'] ?? 'info';
                        $color = match ($priority) {
                            'danger' => '#ef4444',
                            'warning' => '#f59e0b',
                            'success' => '#22c55e',
                            default => '#3b82f6',
                        };
                        $title = $notification->data['title'] ?? $notification->title();
                        $message = $notification->data['message'] ?? $notification->message();
                        $url = $notification->actionUrl();
                        $isRead = $notification->read_at !== null;
                    @endphp
                    <div class="bledi-notif-item {{ $isRead ? 'bledi-notif-item--read' : '' }}">
                        <span class="bledi-notif-dot" style="background-color: {{ $color }};"></span>

                        <button
                            type="button"
                            wire:click="open('{{ $notification->id }}')"
                            class="bledi-notif-item__body"
                        >
                            <span class="bledi-notif-item__title">{{ $title }}</span>
                            <span class="bledi-notif-item__message">{{ $message }}</span>
                            <span class="bledi-notif-item__time">{{ $notification->created_at->diffForHumans() }}</span>
                        </button>

                        <div class="bledi-notif-item__actions">
                            @unless ($isRead)
                                <button
                                    type="button"
                                    wire:click="markRead('{{ $notification->id }}')"
                                    title="{{ __('admin.notifications.mark_read') }}"
                                    class="bledi-notif-icon-btn"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 12.75l6 6 9-13.5"/></svg>
                                </button>
                            @endunless
                            <button
                                type="button"
                                wire:click="delete('{{ $notification->id }}')"
                                title="{{ __('admin.notifications.delete') }}"
                                class="bledi-notif-icon-btn bledi-notif-icon-btn--danger"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="bledi-notif-pagination">
                <button
                    type="button"
                    wire:click="$set('page', {{ $this->notifications->currentPage() - 1 }})"
                    @if(! $this->notifications->onFirstPage()) @else disabled @endif
                    class="bledi-notif-btn bledi-notif-btn--secondary"
                >
                    {{ __('admin.notifications.previous') }}
                </button>
                <span>{{ $this->notifications->currentPage() }} / {{ $this->notifications->lastPage() }}</span>
                <button
                    type="button"
                    wire:click="$set('page', {{ $this->notifications->currentPage() + 1 }})"
                    @if($this->notifications->hasMorePages()) @else disabled @endif
                    class="bledi-notif-btn bledi-notif-btn--secondary"
                >
                    {{ __('admin.notifications.next') }}
                </button>
            </div>
        @endif
    </div>

    <style>
        .bledi-notif-tab {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            border-radius: 0.5rem;
            padding: 0.45rem 0.8rem;
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--gray-600);
            background: transparent;
        }
        .dark .bledi-notif-tab { color: var(--gray-300); }
        .bledi-notif-tab:hover { background: var(--gray-100); }
        .dark .bledi-notif-tab:hover { background: rgb(255 255 255 / 0.08); }
        .bledi-notif-tab--active { background: var(--gray-100); color: var(--gray-950); font-weight: 600; }
        .dark .bledi-notif-tab--active { background: rgb(255 255 255 / 0.1); color: var(--gray-100); }
        .bledi-notif-tab__badge {
            background: var(--primary-500);
            color: #fff;
            border-radius: 9999px;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 0.05rem 0.4rem;
            line-height: 1.2;
        }
        .bledi-notif-filters { display: flex; flex-wrap: wrap; gap: 0.6rem; }
        .bledi-notif-input {
            border: 1px solid var(--gray-200);
            background: var(--gray-50);
            color: var(--gray-900);
            border-radius: 0.5rem;
            padding: 0.45rem 0.7rem;
            font-size: 0.8rem;
            min-width: 12rem;
        }
        .dark .bledi-notif-input { border-color: rgb(255 255 255 / 0.12); background: var(--gray-800); color: var(--gray-100); }
        .bledi-notif-empty {
            padding: 3rem 1rem;
            text-align: center;
            color: var(--gray-500);
            font-size: 0.875rem;
        }
        .bledi-notif-list { display: flex; flex-direction: column; gap: 0.5rem; }
        .bledi-notif-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            border: 1px solid var(--gray-200);
            border-radius: 0.75rem;
            background: var(--gray-50);
        }
        .dark .bledi-notif-item { border-color: rgb(255 255 255 / 0.12); background: var(--gray-800); }
        .bledi-notif-item--read { opacity: 0.65; }
        .bledi-notif-dot { width: 0.6rem; height: 0.6rem; border-radius: 9999px; margin-top: 0.35rem; flex-shrink: 0; }
        .bledi-notif-item__body {
            flex: 1; display: flex; flex-direction: column; gap: 0.1rem; text-align: initial; min-width: 0;
        }
        .bledi-notif-item__title { font-weight: 600; font-size: 0.85rem; color: var(--gray-900); }
        .dark .bledi-notif-item__title { color: var(--gray-100); }
        .bledi-notif-item__message { font-size: 0.8rem; color: var(--gray-600); }
        .dark .bledi-notif-item__message { color: var(--gray-300); }
        .bledi-notif-item__time { font-size: 0.7rem; color: var(--gray-400); margin-top: 0.15rem; }
        .bledi-notif-item__actions { display: inline-flex; gap: 0.3rem; flex-shrink: 0; }
        .bledi-notif-icon-btn {
            display: inline-flex; align-items: center; justify-content: center;
            width: 1.8rem; height: 1.8rem; border-radius: 0.4rem; color: var(--gray-500);
        }
        .bledi-notif-icon-btn:hover { background: var(--gray-200); color: var(--gray-900); }
        .dark .bledi-notif-icon-btn:hover { background: rgb(255 255 255 / 0.1); color: var(--gray-100); }
        .bledi-notif-icon-btn--danger:hover { color: #ef4444; }
        .bledi-notif-btn {
            border-radius: 0.5rem; padding: 0.45rem 0.9rem; font-size: 0.8rem; font-weight: 500;
            color: var(--gray-700); background: transparent; border: 1px solid var(--gray-200);
        }
        .dark .bledi-notif-btn { color: var(--gray-200); border-color: rgb(255 255 255 / 0.12); }
        .bledi-notif-btn:hover:not(:disabled) { background: var(--gray-100); }
        .bledi-notif-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .bledi-notif-btn--secondary { background: var(--gray-100); }
        .dark .bledi-notif-btn--secondary { background: rgb(255 255 255 / 0.06); }
        .bledi-notif-pagination {
            display: flex; align-items: center; justify-content: center; gap: 1rem; padding-top: 0.5rem;
            font-size: 0.8rem; color: var(--gray-600);
        }
    </style>
</x-filament-panels::page>
