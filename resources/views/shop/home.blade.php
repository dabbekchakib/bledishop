<x-shop-layout :title="setting('site.name', config('app.name', 'BlediShop'))">

    {{-- Hero --}}
    <x-storefront.hero
        :title="setting('shop.home_title', __('shop.home_title'))"
        :subtitle="setting('shop.home_description', __('shop.home_description'))"
    />

    @if ((bool) setting('marketing.enabled', false) && \App\Models\Banner::query()->visible(\App\Enums\BannerPosition::Homepage)->exists())
        @php($homeBanners = \App\Models\Banner::query()->visible(\App\Enums\BannerPosition::Homepage)->get())
        <section class="mx-auto grid max-w-7xl grid-cols-1 gap-4 px-4 py-8 sm:grid-cols-2 sm:px-6 lg:grid-cols-3 lg:px-8" aria-label="{{ __('shop.marketing.banners') }}">
            @foreach ($homeBanners as $banner)
                <a href="{{ $banner->link ?: '#' }}" class="group relative block aspect-[16/9] overflow-hidden rounded-2xl border border-border">
                    @if ($banner->image)
                        <img src="{{ storefront_image($banner->image) }}" alt="{{ $banner->title }}" loading="lazy" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                    @endif
                    <div class="absolute inset-0 flex flex-col justify-end bg-gradient-to-t from-black/70 via-black/30 to-transparent p-4">
                        @if (filled($banner->title))
                            <h2 class="text-lg font-bold text-white">{{ $banner->title }}</h2>
                        @endif
                        @if (filled($banner->description))
                            <p class="mt-1 line-clamp-2 text-sm text-white/90">{{ $banner->description }}</p>
                        @endif
                        @if (filled($banner->button_label))
                            <span class="mt-3 inline-flex w-fit items-center gap-1 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-heading">{{ $banner->button_label }}</span>
                        @endif
                    </div>
                </a>
            @endforeach
        </section>
    @endif


    {{-- Trust badges --}}
    <section class="border-y border-border bg-background" aria-label="{{ __('shop.perks') }}">
        <div class="mx-auto grid max-w-7xl grid-cols-1 gap-6 px-4 py-8 sm:grid-cols-3 sm:px-6 lg:px-8">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
                <p class="text-sm font-medium text-heading">{{ __('shop.perk_quality') }}</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
                    </svg>
                </span>
                <p class="text-sm font-medium text-heading">{{ __('shop.perk_shipping') }}</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/>
                    </svg>
                </span>
                <p class="text-sm font-medium text-heading">{{ __('shop.perk_support') }}</p>
            </div>
        </div>
    </section>

    {{-- Featured categories --}}
    @if ($featuredCategories->isNotEmpty())
        <x-storefront.section-heading
            subtitle="{{ __('shop.home_categories_tagline') }}"
            :title="__('shop.home_categories_title')"
            :action-url="localized_route('shop.index')"
            :action-label="__('shop.view_all')"
            class="mx-auto max-w-7xl px-4 pt-12 sm:px-6 lg:px-8"
        >
            <div class="mx-auto grid max-w-7xl grid-cols-2 gap-4 px-4 pb-12 sm:grid-cols-3 sm:px-6 lg:grid-cols-4 lg:px-8">
                @foreach ($featuredCategories as $category)
                    <x-storefront.category-card :category="$category" />
                @endforeach
            </div>
        </x-storefront.section-heading>
    @endif

    {{-- Featured products --}}
    @if ((bool) setting('shop.featured', true) && $featuredProducts->isNotEmpty())
        <x-storefront.section-heading
            subtitle="{{ __('shop.home_featured_tagline') }}"
            :title="__('shop.home_featured_title')"
            :action-url="localized_route('shop.index')"
            :action-label="__('shop.view_all')"
            class="mx-auto max-w-7xl px-4 pt-12 sm:px-6 lg:px-8"
        >
            <div class="mx-auto grid max-w-7xl grid-cols-2 gap-4 px-4 pb-12 sm:grid-cols-3 sm:px-6 lg:grid-cols-4 lg:px-8">
                @foreach ($featuredProducts as $product)
                    <x-storefront.product-card :product="$product" />
                @endforeach
            </div>
        </x-storefront.section-heading>
    @endif

    {{-- New arrivals --}}
    @if ($newProducts->isNotEmpty())
        <x-storefront.section-heading
            subtitle="{{ __('shop.home_new_tagline') }}"
            :title="__('shop.home_new_title')"
            :action-url="localized_route('shop.index')"
            :action-label="__('shop.view_all')"
            class="mx-auto max-w-7xl px-4 pt-12 sm:px-6 lg:px-8"
        >
            <div class="mx-auto grid max-w-7xl grid-cols-2 gap-4 px-4 pb-12 sm:grid-cols-3 sm:px-6 lg:grid-cols-4 lg:px-8">
                @foreach ($newProducts as $product)
                    <x-storefront.product-card :product="$product" />
                @endforeach
            </div>
        </x-storefront.section-heading>
    @endif

    {{-- Promotions --}}
    @if ($promoProducts->isNotEmpty())
        <x-storefront.section-heading
            subtitle="{{ __('shop.home_promo_tagline') }}"
            :title="__('shop.home_promo_title')"
            :action-url="localized_route('shop.index')"
            :action-label="__('shop.view_all')"
            class="mx-auto max-w-7xl px-4 pt-12 sm:px-6 lg:px-8"
        >
            <div class="mx-auto grid max-w-7xl grid-cols-2 gap-4 px-4 pb-12 sm:grid-cols-3 sm:px-6 lg:grid-cols-4 lg:px-8">
                @foreach ($promoProducts as $product)
                    <x-storefront.product-card :product="$product" />
                @endforeach
            </div>
        </x-storefront.section-heading>
    @endif

    {{-- Brands --}}
    @if ($featuredBrands->isNotEmpty())
        <x-storefront.section-heading
            subtitle="{{ __('shop.home_brands_tagline') }}"
            :title="__('shop.home_brands_title')"
            class="mx-auto max-w-7xl px-4 pt-12 sm:px-6 lg:px-8"
        >
            <div class="mx-auto grid max-w-7xl grid-cols-2 gap-4 px-4 pb-12 sm:grid-cols-3 sm:px-6 lg:grid-cols-4 lg:px-8">
                @foreach ($featuredBrands as $brand)
                    <x-storefront.brand-card :brand="$brand" />
                @endforeach
            </div>
        </x-storefront.section-heading>
    @endif

</x-shop-layout>
