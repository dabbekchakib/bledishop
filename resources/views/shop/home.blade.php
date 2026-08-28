<x-shop-layout>
    <section class="bg-background py-20">
        <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
            <p class="text-sm font-semibold uppercase tracking-widest text-primary">{{ __('shop.home_tagline') }}</p>
            <h1 class="mx-auto mt-4 max-w-3xl text-4xl font-extrabold tracking-tight text-heading sm:text-5xl">
                {{ __('shop.home_title') }}
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg text-text-muted">
                {{ __('shop.home_description') }}
            </p>
        </div>
    </section>

    <section class="bg-surface py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-6 sm:grid-cols-3">
                <div class="card p-6">
                    <h2 class="text-lg font-semibold text-heading">{{ __('shop.feature_languages_title') }}</h2>
                    <p class="mt-2 text-sm text-text-muted">{{ __('shop.feature_languages_text') }}</p>
                </div>
                <div class="card p-6">
                    <h2 class="text-lg font-semibold text-heading">{{ __('shop.feature_rtl_title') }}</h2>
                    <p class="mt-2 text-sm text-text-muted">{{ __('shop.feature_rtl_text') }}</p>
                </div>
                <div class="card p-6">
                    <h2 class="text-lg font-semibold text-heading">{{ __('shop.feature_themed_title') }}</h2>
                    <p class="mt-2 text-sm text-text-muted">{{ __('shop.feature_themed_text') }}</p>
                </div>
            </div>
        </div>
    </section>
</x-shop-layout>