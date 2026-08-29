<x-mail::message>
# {{ __('checkout.email.heading', ['order' => $order->order_number], $locale) }}

{{ __('checkout.email.body', [], $locale) }}

<x-mail::table>
| {{ __('checkout.email.column_product', [], $locale) }} | {{ __('checkout.email.column_qty', [], $locale) }} | {{ __('checkout.email.column_price', [], $locale) }} |
| ------------------------------------------------------ | --------------------------------------------------- | ----------------------------------------------------- |
@foreach ($order->items as $item)
| {{ $item->product_name }}{{ $item->variant_name ? ' ('.$item->variant_name.')' : '' }} | {{ $item->quantity }} | {{ format_price($item->unitPriceAmount()) }} |
@endforeach
</x-mail::table>

@if ($order->discount > 0)
- {{ __('checkout.email.discount', [], $locale) }}: {{ format_price($order->discountAmount()) }}
@endif
@if ($order->shipping_amount > 0)
- {{ __('checkout.email.shipping', [], $locale) }}: {{ format_price($order->shippingAmount()) }}
@endif
- **{{ __('checkout.email.total', [], $locale) }}: {{ format_price($order->totalAmount()) }}**

{{ __('checkout.email.footer', [], $locale) }}

{{ config('app.name') }}
</x-mail::message>
