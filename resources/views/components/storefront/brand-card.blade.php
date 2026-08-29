@props(['brand'])

@php
    $name = $brand->translatedName();
    $url = localized_route('shop.brand.show', ['slug' => $brand->translatedSlug()]);
    $logo = storefront_image($brand->logo);
@endphp

<a href="{{ $url }}" class="group flex flex-col items-center justify-center gap-3 rounded-2xl border border-border bg-surface p-6 text-center transition-all duration-300 hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-xl">
    @if ($logo)
        <img src="{{ $logo }}" alt="{{ $name }}" loading="lazy" class="h-12 w-auto object-contain opacity-80 grayscale transition-all duration-300 group-hover:opacity-100 group-hover:grayscale-0">
    @else
        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-surface text-xl font-bold text-text-muted">{{ mb_substr($name, 0, 1) }}</span>
    @endif
    <span class="text-sm font-semibold text-heading group-hover:text-primary">{{ $name }}</span>
</a>
