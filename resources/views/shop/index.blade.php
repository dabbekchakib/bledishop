<x-shop-layout
    :title="__('shop.catalog_title')"
    :meta-description="setting('shop.catalog_meta_description')"
    :canonical="localized_route('shop.index')"
    :schema="[
        app(\App\Services\SeoService::class)->breadcrumbSchema([
            ['name' => __('messages.nav_home'), 'url' => localized_route('home')],
            ['name' => __('shop.nav_shop')],
        ]),
    ]"
>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-storefront.breadcrumbs />

        <div class="mb-8">
            <h1 class="text-3xl font-extrabold tracking-tight text-heading sm:text-4xl">{{ __('shop.catalog_title') }}</h1>
            @if (filled(setting('shop.catalog_intro')))
                <p class="mt-2 max-w-2xl text-text-muted">{{ setting('shop.catalog_intro') }}</p>
            @endif
        </div>

        <div class="lg:grid lg:grid-cols-[16rem_1fr] lg:gap-8">
            {{-- Filters --}}
            <div class="lg:sticky lg:top-20 lg:self-start">
                <form method="GET" action="{{ url()->current() }}" class="rounded-2xl">
                    @include('shop.partials.filters-sidebar', [
                        'showCategories' => true,
                        'categories' => $categories ?? collect(),
                    ])
                </form>
            </div>

            {{-- Products --}}
            <div class="mt-8 lg:mt-0">
                <div class="space-y-6">
                    @include('shop.partials.product-grid', [
                        'emptyTitle' => __('shop.no_products_title'),
                        'emptyMessage' => __('shop.no_products_message'),
                    ])
                </div>
            </div>
        </div>
    </div>

</x-shop-layout>
