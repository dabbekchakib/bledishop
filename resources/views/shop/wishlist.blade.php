<x-shop-layout
    :title="__('shop.wishlist.title')"
    :meta-description="__('shop.wishlist.meta_description')"
>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <x-storefront.breadcrumbs :items="[
            ['label' => __('messages.nav_home'), 'url' => localized_route('home')],
            ['label' => __('shop.wishlist.title')],
        ]" />

        <header class="mb-8 border-b border-border pb-6">
            <h1 class="text-3xl font-extrabold tracking-tight text-heading">{{ __('shop.wishlist.title') }}</h1>
            <p class="mt-2 text-text-muted">{{ __('shop.wishlist.subtitle') }}</p>
        </header>

        @if ($items->isEmpty())
            <div class="rounded-2xl border border-dashed border-border bg-surface p-12 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-primary/10 text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-heading">{{ __('shop.wishlist.empty_title') }}</h2>
                <p class="mt-2 text-text-muted">{{ __('shop.wishlist.empty_text') }}</p>
                <a href="{{ localized_route('shop.index') }}" class="btn-primary mt-6 inline-flex items-center gap-2">
                    {{ __('shop.wishlist.browse_shop') }}
                </a>
            </div>
        @else
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($items as $product)
                    <x-storefront.product-card :product="$product" />
                @endforeach
            </div>
        @endif
    </div>

</x-shop-layout>
