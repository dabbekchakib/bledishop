@props(['product'])

@php
    $name = $product->translatedName();
    $url = localized_route('shop.product.show', ['slug' => $product->translatedSlug()]);
    $image = $product->primaryImageUrl;
    $price = $product->displayPrice();
    $compare = $product->displayCompareAtPrice();
    $isPromoted = $product->isPromoted();
    $discount = $product->discountPercent();
    $promoPrice = $product->promoDisplayPrice();
    $promoPercent = $product->promoDiscountPercent();
    if ($promoPrice !== null && (float) $promoPrice < (float) $price) {
        $compare = $isPromoted && (float) $compare > (float) $price ? $compare : $price;
        $price = (string) $promoPrice;
        $isPromoted = true;
        $discount = $promoPercent ?? $discount;
    }
    $isAvailable = $product->isAvailable();
    $isNew = (int) $product->created_at?->diffInDays(now()) <= 14;
    $brandName = $product->brand?->translatedName();
    $wishlistEnabled = (bool) setting('shop.wishlist_enabled', false);
@endphp

<article class="group relative flex flex-col overflow-hidden rounded-2xl border border-border bg-surface transition-all duration-300 hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-xl">
    <a href="{{ $url }}" class="relative block aspect-square overflow-hidden bg-surface" aria-label="{{ $name }}">

        @if ($image)
            <img src="{{ $image }}" alt="{{ $name }}" loading="lazy"
                 class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
        @else
            <div class="flex h-full w-full items-center justify-center text-text-muted">
                <span class="text-4xl font-bold">{{ mb_substr($name, 0, 1) }}</span>
            </div>
        @endif

        {{-- Badges --}}
        <div class="absolute start-3 top-3 flex flex-col gap-1.5 items-start">
            @if ($discount > 0)
                <x-storefront.badge color="danger">-{{ $discount }}%</x-storefront.badge>
            @endif
            @if ($isNew)
                <x-storefront.badge color="success">{{ __('shop.badge_new') }}</x-storefront.badge>
            @endif
        </div>

        @if (!$isAvailable)
            <div class="absolute inset-0 flex items-center justify-center bg-background/60">
                <x-storefront.badge color="muted" class="border border-border">{{ __('shop.out_of_stock') }}</x-storefront.badge>
            </div>
        @endif
    </a>

    @if ($wishlistEnabled)
        <button
            type="button"
            x-data
            x-on:click.stop="$store.wishlist.toggle(@js((int) $product->id))"
            :disabled="$store.wishlist.busy"
            :aria-pressed="$store.wishlist.contains(@js((int) $product->id))"
            aria-label="{{ __('shop.wishlist.toggle') }}"
            class="absolute end-3 top-3 z-10 inline-flex h-9 w-9 items-center justify-center rounded-full border border-border bg-surface/90 shadow-sm transition-colors hover:border-primary disabled:cursor-not-allowed disabled:opacity-60"
        >
            <svg
                :class="{ 'text-danger fill-danger': $store.wishlist.contains(@js((int) $product->id)), 'text-text-muted': !$store.wishlist.contains(@js((int) $product->id)) }"
                class="h-4.5 w-4.5"
                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"
            >
                <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
            </svg>
        </button>
    @endif

    <div class="flex flex-1 flex-col gap-1 p-4">
        @if ($brandName)
            <p class="text-xs font-medium text-text-muted">{{ $brandName }}</p>
        @endif

        <h3 class="line-clamp-2 text-sm font-semibold text-heading group-hover:text-primary">
            <a href="{{ $url }}">{{ $name }}</a>
        </h3>

        <div class="mt-auto pt-2">
            <x-storefront.price :value="$price" :compare="$isPromoted ? $compare : null" size="base" />
        </div>

        @if ($isAvailable)
            @if ($product->isVariable())
                <a
                    href="{{ $url }}"
                    class="btn-secondary mt-3 w-full justify-center !px-4"
                >
                    {{ __('shop.view_options') }}
                </a>
            @else
                <button
                    type="button"
                    x-data
                    x-on:click="$store.cart.add(@js((int) $product->id), null, 1)"
                    :disabled="$store.cart.busy"
                    class="btn-primary mt-3 w-full justify-center !px-4"
                    :class="{ 'cursor-not-allowed opacity-60': $store.cart.busy }"
                >
                    {{ __('shop.add_to_cart') }}
                </button>
            @endif
        @endif
    </div>
</article>
