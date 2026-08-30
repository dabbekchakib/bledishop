@props(['cart'])

@foreach ($cart['items'] as $item)
    <x-storefront.cart-item :item="$item" :cart="$cart" />
@endforeach
