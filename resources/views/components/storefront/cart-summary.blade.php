@props(['cart', 'sticky' => false])

<div class="{{ $sticky ? 'lg:sticky lg:top-24' : '' }}">
    <div class="rounded-2xl border border-border bg-surface p-6">
        <h2 class="text-lg font-bold text-heading">{{ __('cart.summary') }}</h2>

        @if ((bool) setting('marketing.coupons_enabled', true))
            <div class="mt-4" x-data="{ code: '', loading: false, msg: '', error: false }">
                @if (! empty($cart['coupon_code']))
                    <div class="flex items-center justify-between gap-2 rounded-lg border border-primary/30 bg-primary/5 px-3 py-2 text-sm">
                        <span class="font-semibold text-primary">{{ __('shop.marketing.coupon.applied', ['code' => $cart['coupon_code']]) }}</span>
                        <form method="POST" action="{{ route('shop.cart.coupon.remove', ['locale' => request()->route('locale')]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center gap-1 text-xs font-medium text-danger hover:underline">
                                {{ __('shop.marketing.coupon.remove') }}
                            </button>
                        </form>
                    </div>
                @else
                    <form method="POST" action="{{ route('shop.cart.coupon.apply', ['locale' => request()->route('locale')]) }}">
                        @csrf
                        <div class="flex items-center gap-2">
                            <label for="promo-code" class="sr-only">{{ __('shop.marketing.coupon.title') }}</label>
                            <input
                                id="promo-code"
                                type="text"
                                name="code"
                                maxlength="100"
                                placeholder="{{ __('shop.marketing.coupon.placeholder') }}"
                                class="w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text outline-none ring-primary/30 focus:ring"
                            >
                            <button type="submit" class="btn-ghost shrink-0 !px-4 !py-2 text-sm">{{ __('shop.marketing.coupon.apply') }}</button>
                        </div>
                    </form>
                @endif
                @if (! empty($cart['discount_errors']))
                    <ul class="mt-2 space-y-1 text-xs text-danger">
                        @foreach ($cart['discount_errors'] as $error)
                            <li>{{ __($error) }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        <dl class="mt-5 space-y-3 text-sm">
            <div class="flex items-center justify-between">
                <dt class="text-text-muted">{{ __('cart.subtotal') }}</dt>
                <dd class="font-semibold text-text" data-cart-subtotal="{{ $cart['subtotal'] }}">{{ format_price($cart['subtotal']) }}</dd>
            </div>
            @if ((float) $cart['discount_total'] > 0)
                <div class="flex items-center justify-between text-success">
                    <dt class="flex items-center gap-1.5">{{ __('shop.marketing.discount_label') }}
                        @if (! empty($cart['free_shipping']))
                            <span class="rounded bg-success/10 px-1.5 py-0.5 text-[10px] font-semibold uppercase">{{ __('shop.marketing.free_shipping') }}</span>
                        @endif
                    </dt>
                    <dd class="font-semibold" data-cart-discount="{{ $cart['discount_total'] }}">−{{ format_price($cart['discount_total']) }}</dd>
                </div>
            @endif
            @if ((float) $cart['totals']['tax'] > 0)
                <div class="flex items-center justify-between">
                    <dt class="text-text-muted">{{ __('cart.tax') }}</dt>
                    <dd class="font-semibold text-text" data-cart-tax="{{ $cart['totals']['tax'] }}">{{ format_price($cart['totals']['tax']) }}</dd>
                </div>
            @endif
            @if ((float) $cart['totals']['shipping'] > 0)
                <div class="flex items-center justify-between">
                    <dt class="text-text-muted">{{ __('cart.shipping') }}</dt>
                    <dd class="font-semibold text-text" data-cart-shipping="{{ $cart['totals']['shipping'] }}">{{ format_price($cart['totals']['shipping']) }}</dd>
                </div>
            @else
                <div class="flex items-center justify-between">
                    <dt class="text-text-muted">{{ __('cart.shipping') }}</dt>
                    <dd class="font-semibold text-success">{{ __('cart.shipping_free') }}</dd>
                </div>
            @endif
            <div class="flex items-center justify-between border-t border-border pt-3">
                <dt class="text-base font-semibold text-heading">{{ __('cart.total') }}</dt>
                <dd class="text-2xl font-extrabold text-primary" data-cart-total="{{ $cart['total'] }}">{{ format_price($cart['total']) }}</dd>
            </div>
        </dl>

        @if ((float) $cart['totals']['shipping'] > 0 && (bool) setting('shipping.free_shipping_enabled', false))
            @php
                $threshold = (float) setting('shipping.free_shipping_threshold', 0);
                $remaining = max(0.0, $threshold - (float) $cart['subtotal']);
            @endphp
            @if ($remaining > 0)
                <p class="mt-3 text-xs text-text-muted">
                    {{ __('cart.free_shipping_progress', ['amount' => format_price($remaining)]) }}
                </p>
            @endif
        @endif

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
