<x-account.layout active="orders" :title="__('account.orders_title')">

    <h1 class="text-2xl font-extrabold text-heading">{{ __('account.orders_title') }}</h1>
    <p class="mt-1 text-sm text-text-muted">{{ __('account.orders_intro') }}</p>

    {{-- Filters + search --}}
    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap gap-2" role="group" aria-label="{{ __('account.table_status') }}">
            @php
                $currentStatus = request()->query('status', '');
                $statusFilters = [
                    '' => __('account.filter_all'),
                    'in_progress' => __('account.filter_in_progress'),
                    'pending' => __('account.filter_pending'),
                    'shipped' => __('account.filter_shipped'),
                    'delivered' => __('account.filter_delivered'),
                    'cancelled' => __('account.filter_cancelled'),
                ];
            @endphp
            @foreach ($statusFilters as $value => $label)
                <a href="{{ localized_route('account.orders.index', array_filter(['status' => $value ?: null, 'search' => request()->query('search')])) }}"
                   class="rounded-full border px-3 py-1.5 text-sm font-medium transition-colors {{ $currentStatus === $value ? 'border-primary bg-primary/10 text-primary' : 'border-border text-text-muted hover:border-primary hover:text-primary' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <form action="{{ localized_route('account.orders.index') }}" method="GET" class="flex gap-2" role="search">
            @if ($currentStatus)
                <input type="hidden" name="status" value="{{ $currentStatus }}">
            @endif
            <label for="order-search" class="sr-only">{{ __('account.search_order') }}</label>
            <input id="order-search" name="search" type="search" value="{{ request()->query('search') }}"
                   placeholder="{{ __('account.search_order') }}"
                   class="w-full min-w-0 rounded-lg border-border bg-surface px-3 py-2 text-sm text-text placeholder:text-text-muted focus:border-primary focus:ring-primary sm:w-64">
            <button type="submit" class="btn-primary shrink-0">{{ __('account.search') }}</button>
        </form>
    </div>

    @if ($orders->isEmpty())
        <div class="mt-6 rounded-2xl border border-dashed border-border bg-surface/50 px-6 py-12 text-center">
            <p class="text-sm text-text-muted">{{ request()->hasAny(['status', 'search']) ? __('account.no_matching_orders') : __('account.no_orders') }}</p>
            @unless (request()->hasAny(['status', 'search']))
                <a href="{{ localized_route('shop.index') }}" class="btn-primary mt-5 inline-flex justify-center !px-6">
                    {{ __('account.start_shopping') }}
                </a>
            @endunless
        </div>
    @else
        <div class="mt-6 overflow-hidden rounded-2xl border border-border bg-surface">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border text-start text-xs uppercase tracking-wide text-text-muted">
                            <th scope="col" class="px-5 py-3 text-start font-semibold">{{ __('account.table_order') }}</th>
                            <th scope="col" class="px-5 py-3 text-start font-semibold">{{ __('account.table_date') }}</th>
                            <th scope="col" class="px-5 py-3 text-start font-semibold">{{ __('account.table_status') }}</th>
                            <th scope="col" class="px-5 py-3 text-end font-semibold">{{ __('account.table_total') }}</th>
                            <th scope="col" class="px-5 py-3 text-end font-semibold"><span class="sr-only">{{ __('account.table_actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach ($orders as $order)
                            <tr class="transition-colors hover:bg-surface/70">
                                <td class="px-5 py-4 font-semibold text-heading">{{ $order->order_number }}</td>
                                <td class="px-5 py-4 text-text-muted">{{ $order->created_at->translatedFormat('j M Y') }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold" style="background-color: color-mix(in srgb, var(--color-warning, #D97706) 12%, transparent); color: var(--color-warning, #D97706);">
                                        {{ __('checkout.status.'.$order->status->value) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-end font-bold text-heading">{{ format_price($order->totalAmount()) }}</td>
                                <td class="px-5 py-4 text-end">
                                    <a href="{{ localized_route('account.orders.show', ['orderNumber' => $order->order_number]) }}"
                                       class="text-sm font-medium text-primary hover:underline">
                                        {{ __('account.view_order') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">
            <x-storefront.pagination :paginator="$orders" />
        </div>
    @endif

</x-account.layout>
