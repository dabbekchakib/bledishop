@props([
    'title' => '',
    'metaDescription' => '',
    'canonical' => null,
    'ogImage' => null,
    'ogType' => 'website',
    'schema' => [],
    'bodyClass' => '',
    'robots' => null,
])

@php
    $catalog = app(\App\Services\CatalogService::class);
    $categories = $catalog->categoriesTree();
    $brandsNav = $catalog->featuredBrands(8);
    $cartService = app(\App\Services\CartService::class);
    $cart = $cartService->getCart();

    $wishlistService = app(\App\Services\WishlistService::class);
    $wishlistUser = auth()->user();
    $wishlistIds = $wishlistService->enabled()
        ? $wishlistService->ids($wishlistUser, $wishlistUser === null ? session()->getId() : null)
        : [];

    $seo = app(\App\Services\SeoService::class);

    $siteName = setting('site.name', config('app.name', 'BlediShop'));
    $pageTitle = filled($title)
        ? trim($title).' · '.$siteName
        : (string) setting('seo.title', $siteName);
    $description = filled($metaDescription)
        ? $metaDescription
        : (string) setting('seo.description', '');
    $canonicalUrl = $canonical ?? url()->current();
    $ogImageUrl = $ogImage ?? storefront_logo();
    $robots = $robots ?? (string) setting('seo.robots', 'index, follow');

    $schemaBlocks = array_filter(array_merge(
        [$seo->siteSchema()],
        is_array($schema) ? $schema : [],
    ));

    $promoBar = null;
    if ((bool) setting('marketing.promo_bar_enabled', false)) {
        $pbStart = setting('marketing.promo_bar_starts_at');
        $pbEnd = setting('marketing.promo_bar_ends_at');
        if (
            (blank($pbStart) || now()->gte($pbStart))
            && (blank($pbEnd) || now()->lte($pbEnd))
        ) {
            $promoTexts = setting('marketing.promo_bar_text', []);
            $promoText = is_array($promoTexts)
                ? ($promoTexts[app()->getLocale()] ?? $promoTexts[setting('localization.default_locale', 'fr')] ?? reset($promoTexts))
                : (string) $promoTexts;
            if (filled($promoText)) {
                $promoBar = [
                    'text' => $promoText,
                    'link' => setting('marketing.promo_bar_link'),
                ];
            }
        }
    }
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
        <meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale()) }}">
        <meta property="og:title" content="{{ $pageTitle }}">
        <meta property="og:description" content="{{ $description }}">
        <meta property="og:type" content="{{ $ogType }}">
        <meta property="og:url" content="{{ $canonicalUrl }}">
        @foreach ($localizedUrls as $code => $path)
            <meta property="og:locale:alternate" content="{{ str_replace('_', '-', $code) }}">
        @endforeach
        @if (filled($ogImageUrl))
            <meta property="og:image" content="{{ $ogImageUrl }}">
            <meta property="og:image:alt" content="{{ $pageTitle }}">
        @endif

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $pageTitle }}">
        <meta name="twitter:description" content="{{ $description }}">
        @if (filled($ogImageUrl))
            <meta name="twitter:image" content="{{ $ogImageUrl }}">
        @endif

        @if (setting('seo.schema_org_enabled', true))
            @foreach ($schemaBlocks as $schemaBlock)
                <script type="application/ld+json">{!! $seo->toJson($schemaBlock) !!}</script>
            @endforeach
        @endif

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">

        <x-theme-styles />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen flex-col bg-background font-sans antialiased {{ $bodyClass }}" data-theme-enabled="{{ setting('theme.dark_mode_enabled', false) ? '1' : '0' }}">

        @if ($promoBar)
            <div class="bg-primary px-4 py-2 text-center text-sm font-medium text-primary-inverted">
                @if (filled($promoBar['link']))
                    <a href="{{ $promoBar['link'] }}" class="hover:underline">{{ $promoBar['text'] }}</a>
                @else
                    <span>{{ $promoBar['text'] }}</span>
                @endif
            </div>
        @endif

        <x-storefront.header :categories="$categories" :brands="$brandsNav" :cart="$cart" />

        <x-storefront.mobile-menu :categories="$categories" />

        <main id="main" class="flex-1">
            {{ $slot }}
        </main>

        <x-storefront.footer :categories="$categories" :brands="$brandsNav" />

        <x-storefront.cart-drawer :cart="$cart" />
        <x-storefront.toast />

        <div
            class="hidden"
            aria-hidden="true"
            data-wishlist-state
            data-wishlist-toggle="{{ localized_route('shop.wishlist.toggle') }}"
            data-wishlist-ids="{{ json_encode($wishlistIds) }}"
            x-data
            x-init="$store.wishlist.init()"
        ></div>

        <div class="hidden" aria-hidden="true" x-data @qty-change.window="$store.cart.updateQty($event.detail.key, $event.detail.quantity)"></div>

    </body>
</html>
