<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

/**
 * Centralised SEO helpers so the storefront layers never hand-build meta tags,
 * canonical URLs or JSON-LD fragments. Every value is translatable and driven
 * by the admin configuration.
 */
class SeoService
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly LocalizationService $localizer,
    ) {}

    public function siteName(): string
    {
        return (string) $this->settings->get('site.name', config('app.name', 'BlediShop'));
    }

    public function defaultTitle(): string
    {
        return (string) $this->settings->get('seo.title', $this->siteName());
    }

    public function pageTitle(?string $title): string
    {
        return filled($title)
            ? trim($title).' · '.$this->siteName()
            : $this->defaultTitle();
    }

    public function metaDescription(?string $fallback = null): string
    {
        return (string) ($fallback ?: $this->settings->get('seo.description', ''));
    }

    /**
     * @return array<int, string>
     */
    public function keywords(?string $fallback = null): array
    {
        $keywords = $fallback ?: $this->settings->get('seo.keywords', []);

        if (is_string($keywords)) {
            $keywords = array_filter(array_map('trim', explode(',', $keywords)));
        }

        return is_array($keywords) ? array_values($keywords) : [];
    }

    public function robots(?string $directive = null): string
    {
        return (string) ($directive ?: $this->settings->get('seo.robots', 'index, follow'));
    }

    public function canonicalUrl(?string $url = null): string
    {
        return $url ?: url()->current();
    }

    /**
     * Open Graph image, defaulting to the configured logo or the dedicated
     * default social share image.
     */
    public function ogImage(?string $path = null): ?string
    {
        $path = $path ?: (string) $this->settings->get('site.logo', '');

        if ($path === '') {
            $path = (string) $this->settings->get('seo.social_image', '');
        }

        if (filled($path)) {
            return str_starts_with($path, 'http') ? $path : Storage::url($path);
        }

        return null;
    }

    public function twitterImage(?string $path = null): ?string
    {
        return $this->ogImage($path);
    }

    public function socialName(?string $key): string
    {
        return (string) $this->settings->get("social.{$key}", '');
    }

    /**
     * Per-locale alternates (hreflang) for the current localized request.
     *
     * @return array<string, string> locale => absolute URL
     */
    public function hreflang(): array
    {
        $links = [];

        foreach ($this->localizer->localizedUrlsForCurrentRequest() as $locale => $path) {
            $links[$locale] = url($path);
        }

        return $links;
    }

    /**
     * Global Organization + WebSite JSON-LD.
     *
     * @return array<string, mixed>
     */
    public function siteSchema(): array
    {
        $sameAs = array_values(array_filter([
            $this->socialName('facebook'),
            $this->socialName('instagram'),
            $this->socialName('linkedin'),
            $this->socialName('youtube'),
            $this->socialName('x'),
        ]));

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $this->siteName(),
            'url' => url('/'.$this->localizer->defaultLocale()),
        ];

        if (filled((string) $this->settings->get('site.logo', ''))) {
            $schema['logo'] = $this->ogImage();
        }

        if ($sameAs !== []) {
            $schema['sameAs'] = $sameAs;
        }

        return $schema;
    }

    /**
     * BreadcrumbList JSON-LD.
     *
     * @param  array<int, array{name: string, url?: string}>  $items
     * @return array<string, mixed>
     */
    public function breadcrumbSchema(array $items): array
    {
        $list = [];

        foreach (array_values($items) as $index => $item) {
            $list[] = array_filter([
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'] ?? '',
                'item' => $item['url'] ?? null,
            ], static fn (mixed $value): bool => $value !== null && $value !== '');
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $list,
        ];
    }

    /**
     * Product JSON-LD.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function productSchema(array $data): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $data['name'] ?? null,
            'image' => $data['image'] ?? null,
            'description' => $data['description'] ?? null,
            'sku' => $data['sku'] ?? null,
            'brand' => isset($data['brand']) ? ['@type' => 'Brand', 'name' => $data['brand']] : null,
            'offers' => isset($data['price'])
                ? [
                    '@type' => 'Offer',
                    'priceCurrency' => (string) $this->settings->get('shop.currency', 'TND'),
                    'price' => number_format((float) $data['price'], 2, '.', ''),
                    'availability' => $data['in_stock'] ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                    'url' => $data['url'] ?? null,
                ]
                : null,
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * Renders a value object as a safe JSON-LD script body (HTML-escaped so it
     * can be dropped straight into a <script type="application/ld+json">).
     */
    public function toJson(array $data): string
    {
        return e((string) json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT));
    }
}
