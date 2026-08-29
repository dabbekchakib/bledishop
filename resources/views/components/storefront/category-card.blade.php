@props(['category'])

@php
    $name = $category->translatedName();
    $url = localized_route('shop.category.show', ['slug' => $category->translatedSlug()]);
    $image = storefront_image($category->image);
@endphp

<a href="{{ $url }}" class="group relative flex flex-col overflow-hidden rounded-2xl border border-border bg-surface transition-all duration-300 hover:-translate-y-0.5 hover:shadow-xl">
    <div class="relative aspect-[4/3] overflow-hidden bg-surface">
        @if ($image)
            <img src="{{ $image }}" alt="{{ $name }}" loading="lazy" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
        @else
            <div class="flex h-full w-full items-center justify-center text-4xl font-bold text-text-muted">
                {{ mb_substr($name, 0, 1) }}
            </div>
        @endif
        <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
    </div>
    <div class="flex items-center justify-between gap-2 p-4">
        <div>
            <h3 class="text-sm font-semibold text-heading group-hover:text-primary">{{ $name }}</h3>
            @if ($category->children_count > 0)
                <p class="mt-0.5 text-xs text-text-muted">{{ __('shop.category_subcategories', ['count' => $category->children_count]) }}</p>
            @endif
        </div>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-text-muted transition-transform group-hover:translate-x-0.5 rtl:group-hover:-translate-x-0.5 group-hover:text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75"/>
        </svg>
    </div>
</a>
