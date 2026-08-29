@props(['cart', 'sticky' => false])

<div class="{{ $sticky ? 'lg:sticky lg:top-24' : '' }}">
    <div class="rounded-2xl border border-border bg-surface p-6">
        <h2 class="text-lg font-bold text-heading">{{ __('cart.summary') }}</h2>

        <dl class="mt-5 space-y-3 text-sm">
            <div class="flex items-center justify-between">
                <dt class="text-text-muted">{{ __('cart.subtotal') }}</dt>
                <dd class="font-semibold text-text" data-cart-subtotal="{{ $cart['subtotal'] }}">{{ format_price($cart['subtotal']) }}</dd>
            </div>
            <div class="flex items-center justify-between border-t border-border pt-3">
                <dt class="text-base font-semibold text-heading">{{ __('cart.total') }}</dt>
                <dd class="text-2xl font-extrabold text-primary" data-cart-total="{{ $cart['total'] }}">{{ format_price($cart['total']) }}</dd>
            </div>
        </dl>

        <a
            href="{{ localized_route('shop.checkout') }}"
            class="btn-primary mt-6 w-full justify-center !px-4"
        >
            {{ __('cart.checkout') }}
        </a>
        <p class="mt-3 text-xs text-text-muted">{{ __('cart.checkout_hint') }}</p>

        @if ($cart['line_count'] > 0)
            <button
                type="button"
                x-data
                x-on:click="if (confirm('{{ __('cart.clear_confirm') }}')) $store.cart.clear()"
                class="mt-4 inline-flex w-full items-center justify-center gap-1.5 text-sm font-medium text-text-muted transition-colors hover:text-danger"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.2a51.964 51.964 0 00-3.32 0c-1.18.036-2.09 1.02-2.09 2.2v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                {{ __('cart.clear') }}
            </button>
        @endif
    </div>
</div>
