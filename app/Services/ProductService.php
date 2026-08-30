<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\ProductImageTranslation;
use App\Models\ProductTranslation;
use App\Models\ProductVariant;
use App\Models\ProductVariantValue;
use App\Models\StockMovement;
use App\Services\Concerns\SynchronizesCatalogTranslations;
use App\Support\Sanitizer;
use App\Support\Slugger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductService
{
    use SynchronizesCatalogTranslations;

    protected function translationModel(): string
    {
        return ProductTranslation::class;
    }

    protected function translationForeignKey(): string
    {
        return 'product_id';
    }

    public function create(array $attributes, array $translations, array $options = []): Product
    {
        $this->assertDefaultTranslationPresent($translations);
        $this->assertProductSkuAvailable($attributes['sku'] ?? null, null);

        $categoryIds = $attributes['category_ids'] ?? [];
        $attributeIds = $attributes['attribute_ids'] ?? [];

        $product = DB::transaction(function () use ($attributes, $translations, $options, $categoryIds, $attributeIds): Product {
            unset($attributes['category_ids'], $attributes['attribute_ids']);

            $product = Product::create($attributes);

            $this->syncTranslations($product, $translations);

            $product->categories()->sync($categoryIds);

            $product->attributes()->sync($attributeIds);

            if ($product->isSimple()) {
                $this->initializeSimpleStock($product);
            }

            $this->persistImages($product, $options['images'] ?? []);

            if ($product->isVariable()) {
                $this->persistVariants($product, $options['variants'] ?? []);
            }

            return $product;
        });

        return $product->fresh(['categories', 'attributes', 'translations', 'images', 'variants']);
    }

    public function update(Product $product, array $attributes, array $translations, array $options = []): Product
    {
        $this->assertDefaultTranslationPresent($translations);
        $this->assertProductSkuAvailable(
            $attributes['sku'] ?? $product->sku,
            $product->id,
        );

        $categoryIds = $attributes['category_ids'] ?? [];
        $attributeIds = $attributes['attribute_ids'] ?? [];

        DB::transaction(function () use ($product, $attributes, $translations, $options, $categoryIds, $attributeIds): void {
            unset($attributes['category_ids'], $attributes['attribute_ids']);

            $product->update($attributes);

            $this->syncTranslations($product, $translations);

            $product->categories()->sync($categoryIds);

            $product->attributes()->sync($attributeIds);

            if (array_key_exists('images', $options)) {
                $this->persistImages($product, $options['images'] ?? []);
            }

            if ($product->isVariable() && array_key_exists('variants', $options)) {
                $this->persistVariants($product, $options['variants'] ?? []);
            }
        });

        return $product->fresh(['categories', 'attributes', 'translations', 'images', 'variants']);
    }

    /**
     * Persist the multilingual product translations (including the short
     * description which is specific to products).
     *
     * @param  array<string, array<string, mixed>>  $translations
     */
    public function syncTranslations(Model $entity, array $translations): void
    {
        $table = (new ProductTranslation)->getTable();

        foreach ($this->enabledLocales() as $locale) {
            $fields = $translations[$locale] ?? [];
            $name = trim((string) ($fields['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $customSlug = trim((string) ($fields['slug'] ?? ''));
            $slug = Slugger::unique(
                $customSlug !== '' ? Slugger::make($customSlug, $locale) : Slugger::make($name, $locale),
                $locale,
                $table,
                $this->existingTranslationId($entity, $locale),
            );

            ProductTranslation::updateOrCreate(
                [
                    'product_id' => $entity->id,
                    'locale' => $locale,
                ],
                [
                    'name' => $name,
                    'slug' => $slug,
                    'short_description' => Sanitizer::clean((string) ($fields['short_description'] ?? '')),
                    'description' => Sanitizer::clean((string) ($fields['description'] ?? '')),
                    'meta_title' => trim((string) ($fields['meta_title'] ?? '')),
                    'meta_description' => trim((string) ($fields['meta_description'] ?? '')),
                    'meta_keywords' => trim((string) ($fields['meta_keywords'] ?? '')),
                ],
            );
        }
    }

    /**
     * Build the "translations" form data from an existing product.
     */
    public function translationFormData(Model $entity): array
    {
        $data = [];

        foreach ($this->enabledLocales() as $locale) {
            $translation = $entity->translations()->where('locale', $locale)->first();

            $data[$locale] = $translation?->only([
                'name',
                'slug',
                'short_description',
                'description',
                'meta_title',
                'meta_description',
                'meta_keywords',
            ]) ?? [];
        }

        return $data;
    }

    /**
     * Replace the gallery of a product. A primary image is guaranteed when the
     * platform decides to use the storage disk.
     *
     * @param  array<int, array<string, mixed>>  $images
     */
    public function persistImages(Product $product, array $images): void
    {
        $product->images()->delete();

        $seenPrimary = false;

        foreach ($images as $order => $image) {
            $path = trim((string) ($image['path'] ?? $image['image'] ?? ''));

            if ($path === '') {
                continue;
            }

            $primary = ! $seenPrimary && filter_var($image['is_primary'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $seenPrimary = $seenPrimary || $primary;

            $saved = $product->images()->create([
                'product_variant_id' => ! empty($image['product_variant_id']) ? $image['product_variant_id'] : null,
                'path' => $path,
                'alt' => $image['alt'] ?? null,
                'sort_order' => $order,
                'is_primary' => $primary,
            ]);

            $this->syncImageTranslations($saved, $image['translations'] ?? []);
        }
    }

    /**
     * Ensure every simple/variable product without an explicit primary image
     * still exposes one.
     */
    public function ensurePrimaryImage(Product $product): void
    {
        if ($product->images()->where('is_primary', true)->exists()
            || ! $product->images()->exists()) {
            return;
        }

        $first = $product->images()->orderBy('sort_order')->orderBy('id')->first();

        if ($first !== null) {
            $first->update(['is_primary' => true]);
        }
    }

    private function syncImageTranslations(Model $image, array $translations): void
    {
        foreach ($this->enabledLocales() as $locale) {
            $alt = trim((string) ($translations[$locale]['alt'] ?? ''));

            if ($alt === '') {
                continue;
            }

            ProductImageTranslation::updateOrCreate(
                [
                    'product_image_id' => $image->id,
                    'locale' => $locale,
                ],
                ['alt' => $alt],
            );
        }
    }

    /**
     * Replace the variants of a variable product. Every variant must carry a
     * unique attribute-value selection and an optional SKU.
     *
     * @param  array<int, array<string, mixed>>  $variants
     */
    public function persistVariants(Product $product, array $variants): void
    {
        $oldVariantIds = $product->variants()->withTrashed()->pluck('id');
        ProductVariantValue::whereIn('variant_id', $oldVariantIds)->delete();
        $product->stockMovements()->delete();
        $product->variants()->withTrashed()->forceDelete();

        $seenCombinations = [];

        foreach ($variants as $variant) {
            $selection = $this->normalizeSelection($variant['selection'] ?? []);
            $key = $this->combinationKey($selection);

            if (isset($seenCombinations[$key])) {
                throw ValidationException::withMessages([
                    'variants' => 'Une combinaison d\'attributs identique existe déjà dans la liste.',
                ]);
            }
            $seenCombinations[$key] = true;

            $sku = trim((string) ($variant['sku'] ?? '')) ?: null;

            if ($sku !== null) {
                $this->assertSkuAvailable($sku, $product, null);
            }

            $saved = $product->variants()->create([
                'sku' => $sku,
                'price' => $this->nullableDecimal($variant['price'] ?? null),
                'compare_at_price' => $this->nullableDecimal($variant['compare_at_price'] ?? null),
                'cost_price' => $this->nullableDecimal($variant['cost_price'] ?? null),
                'manage_stock' => (bool) ($variant['manage_stock'] ?? true),
                'stock_quantity' => (int) ($variant['stock_quantity'] ?? 0),
                'low_stock_threshold' => (int) ($variant['low_stock_threshold'] ?? 0),
                'weight' => $this->nullableDecimal($variant['weight'] ?? null),
                'image' => $variant['image'] ?? null,
            ]);

            foreach ($selection as $attributeId => $valueId) {
                $saved->variantValues()->create([
                    'attribute_id' => $attributeId,
                    'attribute_value_id' => $valueId,
                ]);
            }

            if ($saved->manage_stock) {
                $this->recordVariantInitial($product, $saved, (int) $saved->stock_quantity);
            }
        }
    }

    private function recordVariantInitial(Product $product, ProductVariant $variant, int $quantity): void
    {
        StockMovement::create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'type' => StockMovementType::Initial->value,
            'quantity' => $quantity,
            'reason' => 'Création de la variante',
        ]);
    }

    private function initializeSimpleStock(Product $product): void
    {
        if (! $product->manage_stock) {
            return;
        }

        StockMovement::create([
            'product_id' => $product->id,
            'type' => StockMovementType::Initial->value,
            'quantity' => (int) $product->stock_quantity,
            'reason' => 'Création du produit',
        ]);
    }

    /**
     * Accepts either a list of attribute rows ({attribute_id, attribute_value_id})
     * or an attribute_id => attribute_value_id map, and returns a normalized,
     * sorted map.
     *
     * @param  array<string, mixed>  $selection
     * @return array<int, int> attribute_id => attribute_value_id
     */
    private function normalizeSelection(array $selection): array
    {
        $normalized = [];

        $first = reset($selection);

        if (is_array($first)) {
            foreach ($selection as $row) {
                $attributeId = (int) ($row['attribute_id'] ?? 0);
                $valueId = (int) ($row['attribute_value_id'] ?? 0);

                if ($attributeId <= 0 || $valueId <= 0) {
                    continue;
                }

                if (isset($normalized[$attributeId])) {
                    throw ValidationException::withMessages([
                        'variants' => 'Un même attribut ne peut figurer qu\'une seule fois par variante.',
                    ]);
                }

                $normalized[$attributeId] = $valueId;
            }
        } else {
            foreach ($selection as $attributeId => $valueId) {
                $attributeId = (int) $attributeId;
                $valueId = (int) $valueId;

                if ($attributeId <= 0 || $valueId <= 0) {
                    continue;
                }

                $normalized[$attributeId] = $valueId;
            }
        }

        if (count($normalized) === 0) {
            throw ValidationException::withMessages([
                'variants' => 'Chaque variante doit posséder au moins un attribut.',
            ]);
        }

        ksort($normalized);

        return $normalized;
    }

    /**
     * @param  array<int, int>  $normalized
     */
    private function combinationKey(array $normalized): string
    {
        $parts = [];

        foreach ($normalized as $attributeId => $valueId) {
            $parts[] = "{$attributeId}:{$valueId}";
        }

        return implode('|', $parts);
    }

    public function assertSkuAvailable(string $sku, Product $product, ?int $ignoreVariantId = null): void
    {
        $query = ProductVariant::query()->where('sku', $sku);

        if ($product->exists) {
            $query->where('product_id', '!=', $product->id);
        }

        if ($ignoreVariantId !== null) {
            $query->where('id', '!=', $ignoreVariantId);
        }

        if ($query->withTrashed()->exists()) {
            throw ValidationException::withMessages([
                'sku' => 'Cette référence SKU est déjà utilisée.',
            ]);
        }
    }

    public function assertProductSkuAvailable(?string $sku, ?int $ignoreProductId = null): void
    {
        if ($sku === null || $sku === '') {
            return;
        }

        $query = Product::query()->where('sku', $sku);

        if ($ignoreProductId !== null) {
            $query->where('id', '!=', $ignoreProductId);
        }

        if ($query->withTrashed()->exists()) {
            throw ValidationException::withMessages([
                'sku' => 'Cette référence SKU est déjà utilisée.',
            ]);
        }
    }

    /**
     * Normalize a monetary/dimension value for storage (never trust floats).
     */
    private function nullableDecimal($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return number_format((float) $value, 4, '.', '');
    }
}
