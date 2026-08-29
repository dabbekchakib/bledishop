@props(['cart'])

<div class="flex h-full flex-col">
    <div class="flex items-center justify-between border-b border-border px-5 py-4">
        <h2 class="text-base font-bold text-heading">{{ __('cart.drawer_title') }}</h2>
        <button
            type="button"
            x-on:click="$store.cart.closeDrawer()"
            class="inline-flex h-8 w-8 items-center justify-center rounded-md text-text-muted hover:bg-surface hover:text-heading"
            :aria-label="@js(__('shop.close'))"
        aria-label="{{ __('shop.close') }}"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    @if (empty($cart['items']))
        <div class="flex flex-1 flex-col items-center justify-center gap-4 px-6 py-12 text-center">
            <span class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-primary/10 text-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/></svg>
            </span>
            <p class="text-sm font-medium text-heading">{{ __('cart.drawer_empty') }}</p>
            <a href="{{ localized_route('shop.index') }}" class="btn-secondary w-full justify-center !px-4">{{ __('cart.continue_shopping') }}</a>
        </div>
    @else
        <div class="flex-1 divide-y divide-border overflow-y-auto px-5">
            @foreach ($cart['items'] as $item)
                <div class="flex gap-3 py-4">
                    <a href="{{ $item['url'] }}" class="h-16 w-16 shrink-0 overflow-hidden rounded-lg border border-border bg-surface" aria-hidden="true" tabindex="-1">
                        @if ($item['image'])
                            <img src="{{ $item['image'] }}" alt="" loading="lazy" class="h-full w-full object-cover">
                        @else
                            <span class="flex h-full w-full items-center justify-center text-lg font-bold text-text-muted">{{ mb_substr($item['name'], 0, 1) }}</span>
                        @endif
                    </a>
                    <div class="flex min-w-0 flex-1 flex-col gap-1">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <a href="{{ $item['url'] }}" class="line-clamp-2 text-sm font-semibold text-heading hover:text-primary">{{ $item['name'] }}</a>
                                @if ($item['variant_label'])
                                    <p class="text-xs text-text-muted">{{ $item['variant_label'] }}</p>
                                @endif
                            </div>
                            <button
                                type="button"
                                x-on:click="$store.cart.remove(@js($item['key']))"
                                class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-text-muted hover:bg-danger/10 hover:text-danger"
                                :aria-label="@js(__('cart.remove_item'))"
                            aria-label="{{ __('cart.remove_item') }}"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <x-storefront.quantity-selector :item="$item" size="sm" />
                            <div class="text-end">
                                <p class="text-xs text-text-muted">{{ format_price($item['unit_price']) }}</p>
                                @if ($item['quantity'] > 1)
                                    <p class="text-sm font-bold text-heading">{{ format_price($item['line_total']) }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="border-t border-border px-5 py-4">
            <div class="mb-4 flex items-center justify-between">
                <span class="text-sm text-text-muted">{{ __('cart.subtotal') }}</span>
                <span class="text-lg font-bold text-heading">{{ format_price($cart['subtotal']) }}</span>
            </div>
            <a href="{{ localized_route('shop.cart.show') }}" class="btn-secondary w-full justify-center !px-4">{{ __('cart.view_cart') }}</a>
            <a href="{{ localized_route('shop.checkout') }}" class="btn-primary mt-2 w-full justify-center !px-4">{{ __('cart.checkout') }}</a>
        </div>
    @endif
</div>
