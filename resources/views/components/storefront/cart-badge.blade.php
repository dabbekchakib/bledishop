@props(['cart'])

<button
    type="button"
    x-data
    data-cart-base="{{ localized_route('shop.cart.show') }}"
    data-cart-count="{{ $cart['count'] }}"
    x-on:click="$store.cart.openDrawer()"
    class="relative inline-flex h-10 items-center justify-center rounded-md px-2 text-header-text transition-colors hover:text-primary"
    aria-label="{{ __('shop.cart') }}"
    :aria-expanded="$store.cart.drawerOpen.toString()"
>
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/>
    </svg>

    <span
        x-show="$store.cart.count > 0"
        x-cloak
        x-text="$store.cart.count"
        class="absolute -top-0.5 -end-0.5 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-bold text-white"
        aria-hidden="true"
    >{{ $cart['count'] }}</span>
</button>
