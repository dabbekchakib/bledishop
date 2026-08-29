<x-shop-layout
    :title="__('checkout.confirmation_title')"
    :meta-description="__('checkout.meta_description')"
    robots="noindex, nofollow"
>

    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-col items-center text-center">
            <span class="inline-flex h-20 w-20 items-center justify-center rounded-full bg-success/10 text-success">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </span>
            <h1 class="mt-5 text-2xl font-extrabold text-heading sm:text-3xl">{{ __('checkout.confirmation_title') }}</h1>
            <p class="mt-2 flex flex-wrap items-center justify-center gap-1.5 text-sm text-text-muted">
                {{ __('checkout.confirmation_status') }}
                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold text-warning" style="background-color: color-mix(in srgb, var(--color-warning, #D97706) 12%, transparent);">
                    {{ __('checkout.status.'.$order->status->value) }}
                </span>
            </p>
        </div>

        <div class="mt-8 rounded-2xl border border-border bg-surface p-6">
            <h2 class="text-lg font-bold text-heading">{{ __('checkout.order_number_label') }}</h2>
            <p class="mt-1 text-2xl font-extrabold tracking-tight text-primary">{{ $order->order_number }}</p>
            <p class="mt-3 text-sm text-text-muted">{{ __('checkout.confirmation_email_hint', ['email' => $order->customer_email]) }}</p>
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

        <div class="mt-8 text-center">
            <a href="{{ localized_route('shop.index') }}" class="btn-primary inline-flex justify-center !px-6">
                {{ __('checkout.continue_shopping') }}
            </a>
        </div>
    </div>

</x-shop-layout>
