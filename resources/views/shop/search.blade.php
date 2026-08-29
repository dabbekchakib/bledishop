<x-shop-layout
    :title="__('shop.search_title')"
    :canonical="localized_route('shop.search')"
>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-storefront.breadcrumbs :items="[
            ['label' => __('shop.search_title')],
        ]" />

        <div class="mb-8">
            <h1 class="text-3xl font-extrabold tracking-tight text-heading sm:text-4xl">
                {{ __('shop.search_results_for') }}
            </h1>
            <p class="mt-2 text-text-muted">
                {{ __('shop.search_query_label') }} <span class="font-semibold text-heading">"{{ $query }}"</span>
            </p>
        </div>

        <div class="lg:grid lg:grid-cols-[16rem_1fr] lg:gap-8">
            {{-- Filters --}}
            <div class="lg:sticky lg:top-20 lg:self-start">
                <form method="GET" action="{{ url()->current() }}" class="rounded-2xl">
                    <input type="hidden" name="q" value="{{ $query }}">
                    @include('shop.partials.filters-sidebar', [
                        'showCategories' => true,
                        'categories' => $categories ?? collect(),
                    ])
                </form>
            </div>

            {{-- Results --}}
            <div class="mt-8 lg:mt-0">
                <div class="space-y-6">
                    @include('shop.partials.product-grid', [
                        'emptyTitle' => __('shop.no_results_title'),
                        'emptyMessage' => __('shop.no_results_message'),
                    ])
                </div>
            </div>
        </div>
    </div>

</x-shop-layout>
