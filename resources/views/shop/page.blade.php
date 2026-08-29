<x-shop-layout
    :title="$translation?->meta_title ?: $page->translatedTitle()"
    :meta-description="$translation?->meta_description ?: ($translation?->excerpt ?: null)"
    :canonical="localized_route('shop.page.show', ['slug' => $page->translatedSlug()])"
    :og-image="filled($translation?->og_image) ? storefront_image($translation->og_image) : null"
    :robots="$translation?->robots"
    :schema="[$breadcrumbs]"
>

    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <x-storefront.breadcrumbs :items="[
            ['label' => __('messages.nav_home'), 'url' => localized_route('home')],
            ['label' => $page->translatedTitle()],
        ]" />

        <article class="rounded-2xl border border-border bg-surface p-6 sm:p-8">
            <header class="mb-6">
                <h1 class="text-3xl font-extrabold tracking-tight text-heading sm:text-4xl">{{ $page->translatedTitle() }}</h1>
                @if (filled($translation?->excerpt))
                    <p class="mt-3 text-lg text-text-muted">{{ $translation->excerpt }}</p>
                @endif
            </header>

            <div class="cms-content prose prose-lg prose-headings:text-heading prose-a:text-primary max-w-none">
                {!! \App\Support\Sanitizer::clean($page->translatedContent()) !!}
            </div>
        </article>
    </div>

</x-shop-layout>
