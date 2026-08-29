<x-shop-layout
    :title="$category->translation()?->meta_title ?: $category->translatedName()"
    :meta-description="$category->translation()?->meta_description ?: $category->translatedDescription()"
    :canonical="localized_route('shop.category.show', ['slug' => $category->translatedSlug()])"
    :og-image="storefront_image($category->image)"
    :robots="$category->translation()?->robots"
    :schema="[
        app(\App\Services\SeoService::class)->breadcrumbSchema([
            ['name' => __('messages.nav_home'), 'url' => localized_route('home')],
            ['name' => __('shop.nav_shop'), 'url' => localized_route('shop.index')],
            ['name' => $category->translatedName()],
        ]),
    ]"
>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-storefront.breadcrumbs :items="[
            ['label' => __('shop.nav_shop'), 'url' => localized_route('shop.index')],
            ['label' => $category->translatedName()],
        ]" />

        {{-- Category header --}}
        <div class="mb-8 flex flex-col gap-6 sm:flex-row sm:items-center">
            @php($image = storefront_image($category->image))
            <div class="sm:w-40 sm:shrink-0">
                @if ($image)
                    <img src="{{ $image }}" alt="{{ $category->translatedName() }}" class="h-32 w-full rounded-2xl object-cover sm:h-32">
                @else
                    <div class="flex h-32 w-full items-center justify-center rounded-2xl bg-surface text-4xl font-bold text-text-muted">
                        {{ mb_substr($category->translatedName(), 0, 1) }}
                    </div>
                @endif
            </div>
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-heading sm:text-4xl">{{ $category->translatedName() }}</h1>
                @if (filled($category->translatedDescription()))
                    <p class="mt-2 max-w-2xl text-text-muted">{{ $category->translatedDescription() }}</p>
                @endif
            </div>
        </div>

        <div class="lg:grid lg:grid-cols-[16rem_1fr] lg:gap-8">
            {{-- Filters --}}
            <div class="lg:sticky lg:top-20 lg:self-start">
                <form method="GET" action="{{ url()->current() }}" class="rounded-2xl">
                    @include('shop.partials.filters-sidebar', [
                        'showCategories' => false,
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
