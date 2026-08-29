@props(['product'])

@php
    $name = $product->translatedName();
    $url = localized_route('shop.product.show', ['slug' => $product->translatedSlug()]);
    $image = $product->primaryImageUrl;
    $price = $product->displayPrice();
    $compare = $product->displayCompareAtPrice();
    $isPromoted = $product->isPromoted();
    $discount = $product->discountPercent();
    $isAvailable = $product->isAvailable();
    $isNew = (int) $product->created_at?->diffInDays(now()) <= 14;
    $brandName = $product->brand?->translatedName();
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
    </div>
</article>
