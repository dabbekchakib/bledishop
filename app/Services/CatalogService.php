<?php

namespace App\Services;

use App\Enums\StockStatus;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\BrandTranslation;
use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\Product;
use App\Models\ProductTranslation;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Public-facing catalog queries for the storefront. Everything exposed here is
 * restricted to publicly visible records (active status, in-stock rules) and
 * eager loads the multilingual/relations needed to avoid N+1 round trips.
 */
class CatalogService
{
    /**
     * SQL expression resolving a product's effective selling price: the lowest
     * variant price for variable products, otherwise the product's own price.
     */
    private const PRICE_EXPR = '(COALESCE((SELECT MIN(price) FROM product_variants WHERE product_id = products.id AND price IS NOT NULL), products.price))';

    public function __construct(private readonly LocalizationService $localization) {}

    public function locale(): string
    {
        return $this->localization->currentLocale();
    }

    public function defaultLocale(): string
    {
        return $this->localization->defaultLocale();
    }

    /**
     * Full active category tree used by the mega menu and footer.
     */
    public function categoriesTree(): Collection
    {
        return Category::query()
            ->public()
            ->ordered()
            ->with(['translations', 'children' => fn ($q) => $q->public()->ordered()->with('children.translations')])
            ->whereNull('parent_id')
            ->get();
    }

    public function featuredCategories(int $limit = 8): Collection
    {
        return Category::query()
            ->public()
            ->featured()
            ->ordered()
            ->limit($limit)
            ->with('translations')
            ->get();
    }

    public function featuredBrands(int $limit = 10): Collection
    {
        return Brand::query()
            ->public()
            ->featured()
            ->ordered()
            ->limit($limit)
            ->with('translations')
            ->get();
    }

    /**
     * Every active brand, for the filter sidebar.
     */
    public function availableBrands(): Collection
    {
        return Brand::query()
            ->public()
            ->ordered()
            ->with('translations')
            ->get();
    }

    /**
     * Active attributes with their active values, for the filter sidebar.
     */
    public function catalogAttributes(): Collection
    {
        return Attribute::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->with([
                'translations',
                'values' => fn ($q) => $q->active()->orderBy('sort_order')->orderBy('id')->with('translations'),
            ])
            ->get();
    }

    public function featuredProducts(int $limit = 8): Collection
    {
        return $this->baseProductQuery()->public()->featured()->limit($limit)->get();
    }

    public function newProducts(int $limit = 8): Collection
    {
        return $this->baseProductQuery()
            ->public()
            ->whereNotNull('published_at')
            ->latest('published_at')
            ->limit($limit)
            ->get();
    }

    public function promoProducts(int $limit = 8): Collection
    {
        return $this->baseProductQuery()
            ->public()
            ->simple()
            ->whereNotNull('compare_at_price')
            ->whereColumn('compare_at_price', '>', 'price')
            ->where('compare_at_price', '>', 0)
            ->orderByRaw('(price / compare_at_price) ASC')
            ->limit($limit)
            ->get();
    }

    /**
     * Products sharing at least one category with the given product.
     */
    public function relatedProducts(Product $product, int $limit = 4): Collection
    {
        $categoryIds = $product->categories->pluck('id');

        return $this->baseProductQuery()
            ->public()
            ->whereKeyNot($product->id)
            ->when($categoryIds->isNotEmpty(), fn (Builder $q): Builder => $q
                ->whereHas('categories', fn (Builder $c): Builder => $c->whereIn('categories.id', $categoryIds)))
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    /**
     * Product-level gallery images (variant images excluded).
     */
    public function productGallery(Product $product): Collection
    {
        return $product->images()
            ->with('translations')
            ->whereNull('product_variant_id')
            ->get();
    }

    /**
     * Attribute groups (with their values) offered by a variable product, for
     * the variant selection UI on the product page.
     *
     * @return array<int, array<string, mixed>>
     */
    public function productAttributeData(Product $product): array
    {
        return $product->attributes
            ->sortBy('sort_order')
            ->filter(fn (Attribute $attribute): bool => $attribute->values->isNotEmpty())
            ->map(fn (Attribute $attribute): array => [
                'id' => $attribute->id,
                'name' => $attribute->translatedName(),
                'type' => $attribute->type?->value ?? 'select',
                'values' => $attribute->values
                    ->map(fn ($value): array => [
                        'id' => $value->id,
                        'label' => $value->translatedLabel(),
                        'value' => $value->value,
                        'color' => $value->color_code,
                    ])
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * Variant selectable data for the product page (used by Alpine.js).
     *
     * @return array<int, array<string, mixed>>
     */
    public function productVariantData(Product $product): array
    {
        $backorder = StockStatus::OnBackorder->value;

        return $product->variants
            ->sortBy('id')
            ->map(fn (ProductVariant $variant): array => [
                'id' => (int) $variant->id,
                'sku' => $variant->sku,
                'price' => format_price($variant->price),
                'price_raw' => (float) $variant->price,
                'compare_at_price' => $variant->compare_at_price !== null
                    ? format_price((float) $variant->compare_at_price)
                    : null,
                'quantity' => $variant->manage_stock ? (int) $variant->stock_quantity : null,
                'available' => ! $variant->manage_stock
                    || $variant->stock_quantity > 0
                    || $variant->stock_status === $backorder,
                'image' => $variant->image !== null ? storefront_image($variant->image) : null,
                'selection' => $variant->variantValues
                    ->mapWithKeys(fn ($value): array => [(int) $value->attribute_id => (int) $value->attribute_value_id])
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * Price ladder used to render a per-variant price range on product cards.
     *
     * @return array{min: string, max: string, single: bool}
     */
    public function productPriceRange(Product $product): array
    {
        if ($product->isSimple()) {
            return [
                'min' => format_price($product->price),
                'max' => format_price($product->price),
                'single' => true,
            ];
        }

        $prices = $product->variants
            ->pluck('price')
            ->map(fn (mixed $price): float => (float) $price)
            ->filter(fn (float $price): bool => $price > 0)
            ->values();

        if ($prices->isEmpty()) {
            $value = $product->price !== null ? format_price($product->price) : format_price(0);

            return ['min' => $value, 'max' => $value, 'single' => true];
        }

        return [
            'min' => format_price($prices->min()),
            'max' => format_price($prices->max()),
            'single' => (float) $prices->min() === (float) $prices->max(),
        ];
    }

    public function sortOptions(): array
    {
        return [
            'newest' => __('shop.sort_newest'),
            'price_asc' => __('shop.sort_price_asc'),
            'price_desc' => __('shop.sort_price_desc'),
            'name_asc' => __('shop.sort_name_asc'),
            'name_desc' => __('shop.sort_name_desc'),
        ];
    }

    public function shopProducts(array $filters = [], string $sort = 'newest', ?int $perPage = null): LengthAwarePaginator
    {
        $query = $this->baseProductQuery()->public();

        $this->applyFilters($query, $filters);
        $this->applySort($query, $sort);

        return $query->paginate($perPage ?? (int) setting('shop.products_per_page', 12))
            ->withQueryString()
            ->onEachSide(1);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function categoryProducts(Category $category, array $filters = [], string $sort = 'newest', ?int $perPage = null): LengthAwarePaginator
    {
        $ids = array_merge([$category->id], $category->descendantIds());

        $query = $this->baseProductQuery()
            ->public()
            ->whereHas('categories', fn (Builder $q): Builder => $q->whereIn('categories.id', $ids));

        $this->applyFilters($query, $filters);
        $this->applySort($query, $sort);

        return $query->paginate($perPage ?? (int) setting('shop.products_per_page', 12))
            ->withQueryString()
            ->onEachSide(1);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function brandProducts(Brand $brand, array $filters = [], string $sort = 'newest', ?int $perPage = null): LengthAwarePaginator
    {
        $query = $this->baseProductQuery()->public()->where('brand_id', $brand->id);

        $this->applyFilters($query, $filters);
        $this->applySort($query, $sort);

        return $query->paginate($perPage ?? (int) setting('shop.products_per_page', 12))
            ->withQueryString()
            ->onEachSide(1);
    }

    public function findCategoryBySlug(string $slug): ?Category
    {
        foreach ([$this->locale(), $this->defaultLocale()] as $locale) {
            $translation = CategoryTranslation::query()->where('locale', $locale)->where('slug', $slug)->first();
            $id = $translation?->category_id;

            if ($id) {
                $category = Category::query()
                    ->public()
                    ->with(['translations', 'children' => fn ($q) => $q->public()->ordered()->with('translations')])
                    ->find($id);

                if ($category) {
                    return $category;
                }
            }
        }

        return null;
    }

    public function findBrandBySlug(string $slug): ?Brand
    {
        foreach ([$this->locale(), $this->defaultLocale()] as $locale) {
            $id = BrandTranslation::query()
                ->where('locale', $locale)
                ->where('slug', $slug)
                ->value('brand_id');

            if ($id) {
                $brand = Brand::query()
                    ->public()
                    ->with('translations')
                    ->find($id);

                if ($brand) {
                    return $brand;
                }
            }
        }

        return null;
    }

    public function findProductBySlug(string $slug): ?Product
    {
        foreach ([$this->locale(), $this->defaultLocale()] as $locale) {
            $id = ProductTranslation::query()
                ->where('locale', $locale)
                ->where('slug', $slug)
                ->value('product_id');

            if ($id) {
                $product = $this->baseProductQuery()->find($id);

                if ($product && $product->status->isVisiblePublicly()) {
                    return $product;
                }
            }
        }

        return null;
    }

    private function baseProductQuery(): Builder
    {
        return Product::query()->with([
            'translations',
            'brand.translations',
            'categories.translations',
            'images.translations',
            'variants.variantValues.attributeValue.translations',
            'variants.variantValues.attribute.translations',
            'attributes.translations',
            'attributes.values.translations',
        ]);
    }

    /**
     * @param  Builder<Product>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (filled($filters['q'] ?? null)) {
            $this->applySearch($query, (string) $filters['q']);
        }

        if (filled($filters['category'] ?? null)) {
            $category = $this->findCategoryBySlug((string) $filters['category']);

            if ($category) {
                $ids = array_merge([$category->id], $category->descendantIds());
                $query->whereHas('categories', fn (Builder $q): Builder => $q->whereIn('categories.id', $ids));
            }
        }

        if (filled($filters['brand'] ?? null)) {
            $brand = $this->findBrandBySlug((string) $filters['brand']);

            if ($brand) {
                $query->where('brand_id', $brand->id);
            }
        }

        if (filled($filters['min_price'] ?? null)) {
            $query->whereRaw(static::PRICE_EXPR.' >= ?', [(float) $filters['min_price']]);
        }

        if (filled($filters['max_price'] ?? null)) {
            $query->whereRaw(static::PRICE_EXPR.' <= ?', [(float) $filters['max_price']]);
        }

        if (filled($filters['availability'] ?? null)) {
            match ($filters['availability']) {
                'in_stock' => $query->inStock(),
                'out_of_stock' => $query->outOfStock(),
                default => null,
            };
        }

        if (filled($filters['attributes'] ?? null) && is_array($filters['attributes'])) {
            foreach ($filters['attributes'] as $attributeId => $valueIds) {
                if (! is_array($valueIds) || $valueIds === []) {
                    continue;
                }

                $clean = array_values(array_filter(array_map('intval', $valueIds)));

                if ($clean === []) {
                    continue;
                }

                $query->whereHas('variants.variantValues', function (Builder $q) use ($attributeId, $clean): void {
                    $q->where('attribute_id', (int) $attributeId)
                        ->whereIn('attribute_value_id', $clean);
                });
            }
        }
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applySearch(Builder $query, string $term): void
    {
        $term = trim($term);
        $locales = array_values(array_unique([$this->locale(), $this->defaultLocale()]));

        $query->where(function (Builder $q) use ($term, $locales): void {
            $q->where('products.sku', 'like', "%{$term}%");

            $q->orWhereHas('translations', fn (Builder $t): Builder => $t
                ->whereIn('locale', $locales)
                ->where(fn (Builder $n): Builder => $n
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%")
                    ->orWhere('short_description', 'like', "%{$term}%")));

            $q->orWhereHas('brand.translations', fn (Builder $t): Builder => $t
                ->whereIn('locale', $locales)
                ->where('name', 'like', "%{$term}%"));

            $q->orWhereHas('categories.translations', fn (Builder $t): Builder => $t
                ->whereIn('locale', $locales)
                ->where('name', 'like', "%{$term}%"));
        });
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'price_asc' => $query->orderByRaw(static::PRICE_EXPR.' ASC')->orderBy('id'),
            'price_desc' => $query->orderByRaw(static::PRICE_EXPR.' DESC')->orderBy('id'),
            'name_asc' => $query->orderBy(
                fn (Builder $q) => $q->select('name')
                    ->from('product_translations')
                    ->whereColumn('product_translations.product_id', 'products.id')
                    ->where('locale', $this->locale())
                    ->limit(1),
            ),
            'name_desc' => $query->orderByDesc(
                fn (Builder $q) => $q->select('name')
                    ->from('product_translations')
                    ->whereColumn('product_translations.product_id', 'products.id')
                    ->where('locale', $this->locale())
                    ->limit(1),
            ),
            default => $query->when($sort === 'newest', fn (Builder $q): Builder => $q->orderByDesc('published_at')->orderBy('id'), fn (Builder $q): Builder => $q->orderByDesc('id')),
        };
    }
}
