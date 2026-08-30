<x-shop-layout
    :title="__('cart.title')"
    :meta-description="__('cart.meta_description')"
    robots="noindex, nofollow"
>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

        @if (empty($cart['items']))
            <div class="flex flex-col items-center justify-center gap-4 py-16 text-center sm:py-24">
                <span class="inline-flex h-24 w-24 items-center justify-center rounded-full bg-primary/10 text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/></svg>
                </span>
                <h1 class="text-2xl font-extrabold text-heading sm:text-3xl">{{ __('cart.empty_title') }}</h1>
                <p class="max-w-md text-text-muted">{{ __('cart.empty_message') }}</p>
                <a href="{{ localized_route('shop.index') }}" class="btn-primary mt-2 !px-6">{{ __('cart.continue_shopping') }}</a>
            </div>
        @else
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-2xl font-extrabold text-heading sm:text-3xl">{{ __('cart.title') }}</h1>
                <a href="{{ localized_route('shop.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:text-primary-hover">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                    {{ __('cart.continue_shopping') }}
                </a>
            </div>

            <p class="mt-1 text-sm text-text-muted">{{ trans_choice('cart.items_label', $cart['count'], ['count' => $cart['count']]) }}</p>

            <div class="mt-6 grid gap-8 lg:grid-cols-[1fr_24rem]">
                <div>
                    <div id="cart-items" class="divide-y divide-border rounded-2xl border border-border bg-surface px-5 py-4 sm:px-6">
                        @include('shop.partials.cart-items', ['cart' => $cart])
                    </div>
                </div>

                <aside id="cart-summary" aria-label="{{ __('cart.summary') }}">
                    <x-storefront.cart-summary :cart="$cart" sticky />
                </aside>
            </div>
        @endif
    </div>

    @if (! empty($cart['items']) && $similar->isNotEmpty())
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <x-storefront.section-heading
                subtitle="{{ __('cart.similar_tagline') }}"
                :title="__('cart.similar_title')"
                class="px-0"
            >
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($similar as $product)
                        <x-storefront.product-card :product="$product" />
                    @endforeach
                </div>
            </x-storefront.section-heading>
        </div>
    @endif

</x-shop-layout>
