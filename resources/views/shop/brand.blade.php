<x-shop-layout
    :title="$brand->translation()?->meta_title ?: $brand->translatedName()"
    :meta-description="$brand->translation()?->meta_description ?: $brand->translatedDescription()"
    :canonical="localized_route('shop.brand.show', ['slug' => $brand->translatedSlug()])"
>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-storefront.breadcrumbs :items="[
            ['label' => __('shop.nav_shop'), 'url' => localized_route('shop.index')],
            ['label' => $brand->translatedName()],
        ]" />

        {{-- Brand header --}}
        <div class="mb-8 flex flex-col gap-6 rounded-2xl border border-border bg-surface p-6 sm:flex-row sm:items-center sm:p-8">
            @php($logo = storefront_image($brand->logo))
            <div class="sm:w-40 sm:shrink-0">
                @if ($logo)
                    <img src="{{ $logo }}" alt="{{ $brand->translatedName() }}" class="h-24 w-full rounded-2xl object-contain">
                @else
                    <div class="flex h-24 w-full items-center justify-center rounded-2xl bg-background text-4xl font-bold text-text-muted">
                        {{ mb_substr($brand->translatedName(), 0, 1) }}
                    </div>
                @endif
            </div>
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-heading sm:text-4xl">{{ $brand->translatedName() }}</h1>
                @if (filled($brand->translatedDescription()))
                    <p class="mt-2 max-w-2xl text-text-muted">{{ $brand->translatedDescription() }}</p>
                @endif
                @if (filled($brand->website))
                    <a href="{{ $brand->website }}" target="_blank" rel="noopener" class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-primary hover:text-heading">
                        {{ __('shop.visit_website') }}
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                        </svg>
                    </a>
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
