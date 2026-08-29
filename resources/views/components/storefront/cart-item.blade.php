@props(['item', 'cart'])

@php
    $product = $item['product'];
    $image = $item['image'];
    $brand = $item['brand'];
    $variantLabel = $item['variant_label'];
@endphp

<div
    x-data="{}"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="cart-line flex gap-4 py-5 first:pt-0 last:pb-0"
>
    <a href="{{ $item['url'] }}" class="relative h-24 w-24 shrink-0 overflow-hidden rounded-xl border border-border bg-surface" aria-hidden="true" tabindex="-1">
        @if ($image)
            <img src="{{ $image }}" alt="" loading="lazy" class="h-full w-full object-cover">
        @else
            <span class="flex h-full w-full items-center justify-center text-2xl font-bold text-text-muted">{{ mb_substr($item['name'], 0, 1) }}</span>
        @endif
    </a>

    <div class="flex min-w-0 flex-1 flex-col">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                @if ($brand)
                    <p class="text-xs font-medium text-text-muted">{{ $brand }}</p>
                @endif
                <a href="{{ $item['url'] }}" class="line-clamp-2 text-sm font-semibold text-heading hover:text-primary">
                    {{ $item['name'] }}
                </a>

                @if ($variantLabel)
                    <p class="mt-1 text-xs text-text-muted">{{ $variantLabel }}</p>
                @endif

                @if (filled($item['sku']))
                    <p class="mt-1 text-xs text-text-muted">{{ __('cart.sku') }}: <span class="font-mono">{{ $item['sku'] }}</span></p>
                @endif

                @if ($item['price_changed'])
                    <p class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-amber-600" dir="ltr">
                        <span class="line-through">{{ format_price($item['old_price']) }}</span>
                        → {{ format_price($item['unit_price']) }}
                        <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700">{{ __('cart.price_updated_badge') }}</span>
                    </p>
                @endif
            </div>

            <button
                type="button"
                x-on:click="$store.cart.remove(@js($item['key']))"
                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-text-muted transition-colors hover:bg-danger/10 hover:text-danger"
                :aria-label="@js(__('cart.remove_item'))"
            aria-label="{{ __('cart.remove_item') }}"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        @if (! $item['available'])
            <p class="mt-2 inline-flex items-center gap-1.5 rounded-md bg-danger/10 px-2 py-1 text-xs font-medium text-danger">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                {{ __('cart.item_not_available') }}
            </p>
        @elseif ($item['quantity_adjusted'])
            <p class="mt-2 inline-flex items-center gap-1.5 rounded-md bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 0a3.75 3.75 0 100 7.5 3.75 3.75 0 000-7.5zm0 0V9m0 0h.008v.008H12V9z"/></svg>
                {{ __('cart.quantity_adjusted_badge', ['count' => $item['quantity']]) }}
            </p>
        @elseif ($item['stock_limit'] !== null && $item['stock_limit'] > 0 && $item['stock_limit'] <= 3)
            <p class="mt-2 text-xs font-medium text-amber-600">{{ __('cart.low_stock', ['count' => $item['stock_limit']]) }}</p>
        @endif

        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
            <x-storefront.quantity-selector :item="$item" size="sm" />
            <div class="text-end">
                <p class="text-xs text-text-muted">{{ format_price($item['unit_price']) }} / {{ __('cart.quantity') }}</p>
                <p class="text-base font-bold text-heading">{{ format_price($item['line_total']) }}</p>
            </div>
        </div>
    </div>
</div>
