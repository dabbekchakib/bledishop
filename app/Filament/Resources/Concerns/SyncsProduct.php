<?php

namespace App\Filament\Resources\Concerns;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;

/**
 * Bridges the product form (translations, categories, attributes, images and
 * variants under dedicated keys) with the ProductService lifecycle. The whole
 * product (including dependencies) is created/updated through the service so
 * the business rules always run inside a single transaction.
 */
trait SyncsProduct
{
    protected ?array $productTranslations = null;

    protected ?array $productCategories = null;

    protected ?array $productAttributes = null;

    protected ?array $productImages = null;

    protected ?array $productVariants = null;

    protected ?string $productType = null;

    /**
     * @return object the product service responsible for the product lifecycle
     */
    abstract protected function productService(): object;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['translations'] = $this->productService()->translationFormData($this->record);

        $data['category_ids'] = $this->record->categories()->pluck('categories.id')->all();
        $data['attribute_ids'] = $this->record->attributes()->pluck('attributes.id')->all();

        $data['images'] = $this->record->images->map(fn (Model $image): array => [
            'id' => $image->id,
            'path' => $image->path,
            'alt' => $image->alt,
            'is_primary' => (bool) $image->is_primary,
            'product_variant_id' => $image->product_variant_id,
            'translations' => $image->translations
                ->mapWithKeys(fn (Model $t): array => [
                    $t->locale => ['alt' => $t->alt],
                ])
                ->all(),
        ])->all();

        $data['variants'] = $this->record->variants
            ->sortBy('id')
            ->map(fn (Model $variant): array => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'price' => $variant->price,
                'compare_at_price' => $variant->compare_at_price,
                'cost_price' => $variant->cost_price,
                'manage_stock' => (bool) $variant->manage_stock,
                'stock_quantity' => $variant->stock_quantity,
                'low_stock_threshold' => $variant->low_stock_threshold,
                'weight' => $variant->weight,
                'image' => $variant->image,
                'selection' => $variant->variantValues
                    ->map(fn (Model $value): array => [
                        'attribute_id' => $value->attribute_id,
                        'attribute_value_id' => $value->attribute_value_id,
                    ])
                    ->values()
                    ->all(),
            ])
            ->all();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->captureProductDependencies($data);

        return $this->stripComplexKeys($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->captureProductDependencies($data);

        return $this->stripComplexKeys($data);
    }

    /**
     * Store the grouped form payloads that are handled by the service.
     *
     * @param  array<string, mixed>  $data
     */
    private function captureProductDependencies(array $data): void
    {
        $this->productTranslations = $this->arrayOf($data['translations'] ?? []);
        $this->productCategories = $this->arrayOf($data['category_ids'] ?? []);
        $this->productAttributes = $this->arrayOf($data['attribute_ids'] ?? []);
        $this->productImages = $this->arrayOf($data['images'] ?? []);
        $this->productVariants = $this->arrayOf($data['variants'] ?? []);
        $this->productType = isset($data['type']) ? (string) $data['type'] : null;
    }

    /**
     * Remove grouped form keys so only scalar product columns remain.
     *
     * @param  array<string, mixed>  $data
     */
    private function stripComplexKeys(array $data): array
    {
        foreach (['translations', 'category_ids', 'attribute_ids', 'images', 'variants'] as $key) {
            unset($data[$key]);
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return $this->productService()->create(
            $data,
            $this->productTranslations ?? [],
            $this->productOptions(),
        );
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Product $record */
        $attributes = array_merge($data, [
            'category_ids' => $this->productCategories ?? [],
            'attribute_ids' => $this->productAttributes ?? [],
        ]);

        return $this->productService()->update(
            $record,
            $attributes,
            $this->productTranslations ?? [],
            $this->productOptions(),
        );
    }

    private function productOptions(): array
    {
        $record = $this->record ?? null;
        $isVariable = $record !== null && $record->exists
            ? $record->isVariable()
            : ($this->productType === 'variable');

        $options = [
            'images' => $this->productImages ?? [],
        ];

        if ($isVariable) {
            $options['variants'] = $this->productVariants ?? [];
        }

        return $options;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function arrayOf(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }
}
