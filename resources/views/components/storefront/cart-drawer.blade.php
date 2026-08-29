@props(['cart'])

<div
    x-data
    x-show="$store.cart.drawerOpen"
    x-cloak
    x-transition.opacity.duration.200ms
    class="fixed inset-0 z-50"
    role="dialog"
    aria-modal="true"
    aria-label="{{ __('cart.drawer_title') }}"
>
    <div class="absolute inset-0 bg-black/40" x-on:click="$store.cart.closeDrawer()"></div>

    <div
        x-show="$store.cart.drawerOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-x-full rtl:-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full rtl:-translate-x-full"
        x-on:keydown.escape.window="$store.cart.closeDrawer()"
        x-init="$watch('$store.cart.drawerOpen', (val) => { if (val) $store.cart.refreshDrawer(); })"
        class="absolute inset-y-0 end-0 flex w-full max-w-md flex-col bg-background shadow-2xl"
    >
        <div id="cart-drawer-inner" class="flex h-full flex-col overflow-hidden">
            <x-storefront.cart-drawer-body :cart="$cart" />
        </div>
    </div>
</div>
