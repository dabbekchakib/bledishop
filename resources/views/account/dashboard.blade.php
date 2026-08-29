<x-account.layout active="dashboard" :title="__('account.dashboard_title')">

    <h1 class="text-2xl font-extrabold text-heading">{{ __('account.dashboard_title') }}</h1>
    <p class="mt-1 text-sm text-text-muted">{{ __('account.dashboard_intro', ['name' => $user->fullName()]) }}</p>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <a href="{{ localized_route('account.orders.index') }}" class="rounded-2xl border border-border bg-surface p-5 transition-colors hover:border-primary">
            <p class="text-3xl font-extrabold text-primary">{{ $ordersCount }}</p>
            <p class="mt-1 text-sm font-medium text-heading">{{ __('account.stats_orders') }}</p>
        </a>
        <a href="{{ localized_route('account.orders.index', ['status' => 'in_progress']) }}" class="rounded-2xl border border-border bg-surface p-5 transition-colors hover:border-primary">
            <p class="text-3xl font-extrabold text-primary">{{ $inProgressCount }}</p>
            <p class="mt-1 text-sm font-medium text-heading">{{ __('account.stats_in_progress') }}</p>
        </a>
        <a href="{{ localized_route('account.orders.index', ['status' => 'delivered']) }}" class="rounded-2xl border border-border bg-surface p-5 transition-colors hover:border-primary">
            <p class="text-3xl font-extrabold text-primary">{{ $deliveredCount }}</p>
            <p class="mt-1 text-sm font-medium text-heading">{{ __('account.stats_delivered') }}</p>
        </a>
        <a href="{{ localized_route('account.orders.index') }}" class="rounded-2xl border border-border bg-surface p-5 transition-colors hover:border-primary">
            <p class="text-3xl font-extrabold text-primary">{{ format_price($totalSpent) }}</p>
            <p class="mt-1 text-sm font-medium text-heading">{{ __('account.stats_total_spent') }}</p>
        </a>
    </div>

    <h2 class="mt-8 text-lg font-bold text-heading">{{ __('account.recent_orders') }}</h2>

    @if ($orders->isEmpty())
        <div class="mt-4 rounded-2xl border border-dashed border-border bg-surface/50 px-6 py-12 text-center">
            <p class="text-sm text-text-muted">{{ __('account.no_orders') }}</p>
            <a href="{{ localized_route('shop.index') }}" class="btn-primary mt-5 inline-flex justify-center !px-6">
                {{ __('account.start_shopping') }}
            </a>
        </div>
    @else
        <div class="mt-4 overflow-hidden rounded-2xl border border-border bg-surface">
            <ul class="divide-y divide-border">
                @foreach ($orders->take(5) as $order)
                    <li>
                        <a href="{{ localized_route('account.orders.show', ['orderNumber' => $order->order_number]) }}"
                           class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 transition-colors hover:bg-surface/70">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-heading">{{ $order->order_number }}</p>
                                <p class="mt-0.5 text-xs text-text-muted">{{ $order->created_at->translatedFormat('j M Y') }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold" style="background-color: color-mix(in srgb, var(--color-warning, #D97706) 12%, transparent); color: var(--color-warning, #D97706);">
                                    {{ __('checkout.status.'.$order->status->value) }}
                                </span>
                                <span class="text-sm font-bold text-heading">{{ format_price($order->totalAmount()) }}</span>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
            @if ($orders->count() > 5)
                <a href="{{ localized_route('account.orders.index') }}" class="block border-t border-border px-5 py-3 text-center text-sm font-medium text-primary hover:underline">
                    {{ __('account.view_all_orders') }}
                </a>
            @endif
        </div>
    @endif

</x-account.layout>
