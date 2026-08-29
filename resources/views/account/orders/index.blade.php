<x-account.layout active="orders" :title="__('account.orders_title')">

    <h1 class="text-2xl font-extrabold text-heading">{{ __('account.orders_title') }}</h1>
    <p class="mt-1 text-sm text-text-muted">{{ __('account.orders_intro') }}</p>

    @if ($orders->isEmpty())
        <div class="mt-6 rounded-2xl border border-dashed border-border bg-surface/50 px-6 py-12 text-center">
            <p class="text-sm text-text-muted">{{ __('account.no_orders') }}</p>
            <a href="{{ localized_route('shop.index') }}" class="btn-primary mt-5 inline-flex justify-center !px-6">
                {{ __('account.start_shopping') }}
            </a>
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
