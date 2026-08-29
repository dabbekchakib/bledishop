@props(['cart'])

<div id="cart-items" class="divide-y divide-border rounded-2xl border border-border bg-surface px-5 py-4 sm:px-6">
    @foreach ($cart['items'] as $item)
        <x-storefront.cart-item :item="$item" :cart="$cart" />
    @endforeach
</div>
