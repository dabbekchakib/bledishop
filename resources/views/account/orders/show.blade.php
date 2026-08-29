<x-account.layout active="orders" :title="__('account.order_detail', ['order' => $order->order_number])">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-extrabold text-heading">{{ __('account.order_detail', ['order' => $order->order_number]) }}</h1>
        <a href="{{ localized_route('account.orders.index') }}" class="text-sm font-medium text-primary hover:underline">
            ← {{ __('account.back_to_orders') }}
        </a>
    </div>

    <div class="mt-6 rounded-2xl border border-border bg-surface p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm font-bold uppercase tracking-wide text-text-muted">{{ __('checkout.order_number_label') }}</p>
                <p class="mt-1 text-2xl font-extrabold tracking-tight text-primary">{{ $order->order_number }}</p>
                <p class="mt-1 text-sm text-text-muted">{{ $order->created_at->translatedFormat('j M Y \a\t H:i') }}</p>
            </div>
            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" style="background-color: color-mix(in srgb, var(--color-warning, #D97706) 12%, transparent); color: var(--color-warning, #D97706);">
                {{ __('checkout.status.'.$order->status->value) }}
            </span>
        </div>
    </div>

    <div class="mt-6 grid gap-6 sm:grid-cols-2">
        <div class="rounded-2xl border border-border bg-surface p-6">
            <h2 class="text-sm font-bold uppercase tracking-wide text-text-muted">{{ __('checkout.shipping_title') }}</h2>
            <address class="mt-3 text-sm not-italic leading-relaxed text-text">
                {{ $order->customerFullName() }}<br>
                {{ $order->customer_phone }}<br>
                {{ $order->shipping_address }}<br>
                @if ($order->shipping_city){{ $order->shipping_city }}<br>@endif
                @if ($order->shipping_postal_code){{ $order->shipping_postal_code }}<br>@endif
                {{ $order->shipping_country }}
            </address>
        </div>

        <div class="rounded-2xl border border-border bg-surface p-6">
            <h2 class="text-sm font-bold uppercase tracking-wide text-text-muted">{{ __('checkout.totals_title') }}</h2>
            <dl class="mt-3 space-y-2 text-sm">
                <div class="flex items-center justify-between">
                    <dt class="text-text-muted">{{ __('checkout.subtotal') }}</dt>
                    <dd class="font-semibold text-text">{{ format_price($order->subtotalAmount()) }}</dd>
                </div>
                @if ($order->discount > 0)
                    <div class="flex items-center justify-between">
                        <dt class="text-text-muted">{{ __('checkout.discount') }}</dt>
                        <dd class="font-semibold text-text">− {{ format_price($order->discountAmount()) }}</dd>
                    </div>
                @endif
                @if ($order->shipping_amount > 0)
                    <div class="flex items-center justify-between">
                        <dt class="text-text-muted">{{ __('checkout.shipping') }}</dt>
                        <dd class="font-semibold text-text">{{ format_price($order->shippingAmount()) }}</dd>
                    </div>
                @endif
                @if ($order->tax_amount > 0)
                    <div class="flex items-center justify-between">
                        <dt class="text-text-muted">{{ __('checkout.tax') }}</dt>
                        <dd class="font-semibold text-text">{{ format_price($order->taxAmount()) }}</dd>
                    </div>
                @endif
                <div class="flex items-center justify-between border-t border-border pt-2">
                    <dt class="font-semibold text-heading">{{ __('checkout.total') }}</dt>
                    <dd class="text-lg font-extrabold text-primary">{{ format_price($order->totalAmount()) }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="mt-6 rounded-2xl border border-border bg-surface">
        <h2 class="border-b border-border px-6 py-4 text-sm font-bold uppercase tracking-wide text-text-muted">{{ __('checkout.items_label') }}</h2>
        <ul class="divide-y divide-border px-6">
            @foreach ($order->items as $item)
                <li class="flex items-start justify-between gap-4 py-4">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-heading">{{ $item->product_name }}</p>
                        @if ($item->variant_name)
                            <p class="mt-0.5 text-xs text-text-muted">{{ $item->variant_name }}</p>
                        @endif
                        @if ($item->sku)
                            <p class="mt-0.5 text-xs text-text-muted">{{ __('checkout.sku') }} : {{ $item->sku }}</p>
                        @endif
                    </div>
                    <div class="text-right rtl:text-left">
                        <p class="text-sm font-semibold text-text">{{ format_price($item->lineTotalAmount()) }}</p>
                        <p class="text-xs text-text-muted">{{ $item->quantity }} × {{ format_price($item->unitPriceAmount()) }}</p>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>

</x-account.layout>
