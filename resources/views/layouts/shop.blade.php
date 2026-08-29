@props([
    'title' => '',
    'metaDescription' => '',
    'canonical' => null,
    'ogImage' => null,
    'bodyClass' => '',
])

@php
    $catalog = app(\App\Services\CatalogService::class);
    $categories = $catalog->categoriesTree();
    $brandsNav = $catalog->featuredBrands(8);

    $siteName = setting('site.name', config('app.name', 'BlediShop'));
    $pageTitle = filled($title)
        ? trim($title).' · '.$siteName
        : (string) setting('seo.title', $siteName);
    $description = filled($metaDescription)
        ? $metaDescription
        : (string) setting('seo.description', '');
    $canonicalUrl = $canonical ?? url()->current();
    $ogImageUrl = $ogImage ?? storefront_logo();
    $robots = (string) setting('seo.robots', 'index, follow');
@endphp

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ current_direction() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="robots" content="{{ $robots }}">

        <title>{{ $pageTitle }}</title>
        <meta name="description" content="{{ $description }}">

        @if (filled(setting('seo.keywords')))
            <meta name="keywords" content="{{ implode(', ', setting('seo.keywords')) }}">
        @endif

        @if (filled(setting('site.favicon')))
            <link rel="icon" href="{{ storefront_image(setting('site.favicon')) }}">
        @endif

        <link rel="canonical" href="{{ $canonicalUrl }}">

        @php($localizedUrls = app(\App\Services\LocalizationService::class)->localizedUrlsForCurrentRequest())
        @foreach ($localizedUrls as $code => $path)
            <link rel="alternate" hreflang="{{ $code }}" href="{{ url($path) }}" />
        @endforeach

        <meta property="og:site_name" content="{{ $siteName }}">
        <meta property="og:title" content="{{ $pageTitle }}">
        <meta property="og:description" content="{{ $description }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ $canonicalUrl }}">
        @if (filled($ogImageUrl))
            <meta property="og:image" content="{{ $ogImageUrl }}">
        @endif
        <meta name="twitter:card" content="summary_large_image">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">

        <x-theme-styles />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen flex-col bg-background font-sans antialiased {{ $bodyClass }}">

        <x-storefront.header :categories="$categories" :brands="$brandsNav" />

        <x-storefront.mobile-menu :categories="$categories" />

        <main id="main" class="flex-1">
            {{ $slot }}
        </main>

        <x-storefront.footer :categories="$categories" :brands="$brandsNav" />

    </body>
</html>
