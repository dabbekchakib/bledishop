<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Builds the sitemap URL collection. Every entry carries localized alternates
 * so search engines can resolve each language variant, and only indexable,
 * public content is included.
 */
class SitemapService
{
    public function __construct(
        private readonly LocalizationService $localizer,
        private readonly CatalogService $catalog,
    ) {}

    /**
     * @return array<int, array{loc: string, alternates?: array<string, string>, lastmod?: string, changefreq?: string, priority?: string}>
     */
    public function urls(): array
    {
        $urls = [];

        foreach ($this->localizer->availableLocales() as $locale) {
            $urls[] = [
                'loc' => $this->localized('home', [], $locale),
                'prior' => '1.0',
            ];
        }

        foreach ($this->localizer->availableLocales() as $locale) {
            $urls[] = [
                'loc' => $this->localized('shop.index', [], $locale),
                'prior' => '0.8',
            ];
        }

        foreach ($this->catalog->availableBrands() as $brand) {
            $urls[] = $this->entityEntry($brand, 'shop.brand.show');
        }

        $this->categoryEntries()->each(function (array $entry) use (&$urls): void {
            $urls[] = $entry;
        });

        $this->pageEntries()->each(function (array $entry) use (&$urls): void {
            $urls[] = $entry;
        });

        foreach ($this->productEntries() as $entry) {
            $urls[] = $entry;
        }

        return $urls;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function categoryEntries(): Collection
    {
        return Category::query()
            ->public()
            ->with('translations')
            ->get()
            ->map(fn (Category $category): array => $this->entityEntry($category, 'shop.category.show'));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function pageEntries(): Collection
    {
        return Page::query()
            ->public()
            ->with('translations')
            ->get()
            ->map(fn (Page $page): array => $this->entityEntry($page, 'shop.page.show'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function productEntries(): array
    {
        return Product::query()
            ->public()
            ->with('translations')
            ->get()
            ->map(fn (Product $product): array => $this->entityEntry($product, 'shop.product.show'))
            ->all();
    }

    /**
     * @param  Brand|Category|Page|Product  $entity
     * @return array<string, mixed>
     */
    private function entityEntry(mixed $entity, string $routeName): array
    {
        $alternates = [];

        foreach ($this->localizer->availableLocales() as $locale) {
            $slug = $this->slugFor($entity, $locale);

            if ($slug === '') {
                continue;
            }

            $alternates[$locale] = $this->localized($routeName, ['slug' => $slug], $locale);
        }

        $default = $this->localizer->defaultLocale();
        $defaultSlug = $this->slugFor($entity, $default);

        if ($defaultSlug === '') {
            $loc = $alternates[$default] ?? array_values($alternates)[0] ?? null;
        } else {
            $loc = $this->localized($routeName, ['slug' => $defaultSlug], $default);
        }

        $entry = ['loc' => $loc];
        $entry['alternates'] = $alternates;
        $entry['lastmod'] = optional($entity->updated_at)->toAtomString() ?: null;
        $entry['prior'] = '0.6';

        return array_filter($entry, static fn (mixed $value): bool => $value !== null);
    }

    /**
     * The translation slug for the requested locale (via the entity's
     * multilingual translation resolution).
     */
    private function slugFor(mixed $entity, string $locale): string
    {
        return $entity->translatedSlug($locale);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function localized(string $routeName, array $parameters, string $locale): string
    {
        return url(route($routeName, array_merge(['locale' => $locale], $parameters), false));
    }
}
