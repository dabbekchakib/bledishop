<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\AttributeService;
use App\Services\ProductService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demonstration products (simple + variable with variants) for the three shop
 * languages. Standalone and idempotent — existing products are detected by
 * their French slug and skipped.
 *
 *   php artisan db:seed --class=ProductSeeder
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedProductAttributes();
            $this->seedSimpleProducts();
            $this->seedVariableProducts();
        });
    }

    private function seedProductAttributes(): void
    {
        $service = app(AttributeService::class);

        $attributes = [
            [
                'code' => 'taille',
                'type' => 'select',
                'values' => [
                    ['value' => 'S', 'labels' => ['fr' => 'S', 'ar' => 'S', 'en' => 'S']],
                    ['value' => 'M', 'labels' => ['fr' => 'M', 'ar' => 'M', 'en' => 'M']],
                    ['value' => 'L', 'labels' => ['fr' => 'L', 'ar' => 'L', 'en' => 'L']],
                ],
                'names' => ['fr' => 'Taille', 'ar' => 'المقاس', 'en' => 'Size'],
            ],
            [
                'code' => 'couleur',
                'type' => 'color',
                'values' => [
                    ['value' => 'Rouge', 'color' => '#dc2626', 'labels' => ['fr' => 'Rouge', 'ar' => 'أحمر', 'en' => 'Red']],
                    ['value' => 'Bleu', 'color' => '#2563eb', 'labels' => ['fr' => 'Bleu', 'ar' => 'أزرق', 'en' => 'Blue']],
                    ['value' => 'Noir', 'color' => '#111827', 'labels' => ['fr' => 'Noir', 'ar' => 'أسود', 'en' => 'Black']],
                ],
                'names' => ['fr' => 'Couleur', 'ar' => 'اللون', 'en' => 'Color'],
            ],
        ];

        foreach ($attributes as $attribute) {
            $exists = Attribute::query()->where('code', $attribute['code'])->exists();

            if ($exists) {
                continue;
            }

            $service->create(
                [
                    'code' => $attribute['code'],
                    'type' => $attribute['type'],
                    'status' => ContentStatus::Active->value,
                    'sort_order' => 0,
                ],
                [
                    'fr' => ['name' => $attribute['names']['fr']],
                    'ar' => ['name' => $attribute['names']['ar']],
                    'en' => ['name' => $attribute['names']['en']],
                ],
                collect($attribute['values'])->map(fn (array $value): array => [
                    'value' => $value['value'],
                    'color_code' => $value['color'] ?? null,
                    'sort_order' => 0,
                    'status_is_active' => true,
                    'translations' => [
                        'fr' => ['label' => $value['labels']['fr']],
                        'ar' => ['label' => $value['labels']['ar']],
                        'en' => ['label' => $value['labels']['en']],
                    ],
                ])->all(),
            );
        }
    }

    private function seedSimpleProducts(): void
    {
        $service = app(ProductService::class);

        $products = [
            [
                'slug' => 'smartphone-x-pro',
                'category' => 'smartphones',
                'brand' => 'apple',
                'names' => [
                    'fr' => 'Smartphone X Pro',
                    'ar' => 'هاتف X برو',
                    'en' => 'Smartphone X Pro',
                ],
                'price' => 2499.00,
                'compare' => 2999.00,
                'stock' => 42,
                'featured' => true,
            ],
            [
                'slug' => 'ordinateur-portable-ultra',
                'category' => 'ordinateurs-portables',
                'brand' => 'samsung',
                'names' => [
                    'fr' => 'Ordinateur portable Ultra',
                    'ar' => 'حاسوب محمول ألترا',
                    'en' => 'Ultra Laptop',
                ],
                'price' => 4200.00,
                'compare' => null,
                'stock' => 15,
                'featured' => true,
            ],
            [
                'slug' => 't-shirt-classique',
                'category' => 'vetements',
                'brand' => 'nike',
                'names' => [
                    'fr' => 'T-shirt classique',
                    'ar' => 'تي شيرت كلاسيكي',
                    'en' => 'Classic T-Shirt',
                ],
                'price' => 79.00,
                'compare' => 99.00,
                'stock' => 0,
                'featured' => false,
            ],
        ];

        foreach ($products as $product) {
            $category = $this->categoryBySlug($product['category']);
            $brand = $this->brandBySlug($product['brand']);

            if ($category === null || $brand === null) {
                continue;
            }

            if ($this->productExists($product['slug'])) {
                continue;
            }

            $service->create(
                [
                    'brand_id' => $brand->id,
                    'type' => ProductType::Simple->value,
                    'status' => ProductStatus::Active->value,
                    'featured' => $product['featured'],
                    'sku' => 'PRD-'.strtoupper($product['slug']),
                    'price' => $product['price'],
                    'compare_at_price' => $product['compare'],
                    'manage_stock' => true,
                    'stock_quantity' => $product['stock'],
                    'low_stock_threshold' => 5,
                    'category_ids' => [$category->id],
                    'attribute_ids' => [],
                ],
                [
                    'fr' => ['name' => $product['names']['fr'], 'slug' => $product['slug']],
                    'ar' => ['name' => $product['names']['ar'], 'slug' => ''],
                    'en' => ['name' => $product['names']['en'], 'slug' => ''],
                ],
            );
        }
    }

    private function seedVariableProducts(): void
    {
        $service = app(ProductService::class);

        $category = $this->categoryBySlug('vetements');
        $brand = $this->brandBySlug('zara');

        $taille = Attribute::where('code', 'taille')->first();
        $couleur = Attribute::where('code', 'couleur')->first();

        if ($category === null || $taille === null || $couleur === null) {
            return;
        }

        $slug = 'pull-premium';
        if ($this->productExists($slug)) {
            return;
        }

        $service->create(
            [
                'brand_id' => $brand?->id,
                'type' => ProductType::Variable->value,
                'status' => ProductStatus::Active->value,
                'featured' => true,
                'manage_stock' => true,
                'low_stock_threshold' => 5,
                'category_ids' => [$category->id],
                'attribute_ids' => [$taille->id, $couleur->id],
            ],
            [
                'fr' => ['name' => 'Pull premium', 'slug' => $slug, 'short_description' => 'Pull en laine mérinos.'],
                'ar' => ['name' => 'كنزة بريميوم', 'slug' => ''],
                'en' => ['name' => 'Premium Sweater', 'slug' => ''],
            ],
            [
                'variants' => $this->variantPayloads($taille, $couleur),
            ],
        );
    }

    /**
     * Build variant payloads for the Taille x Couleur attribute combination.
     *
     * @return array<int, array<string, mixed>>
     */
    private function variantPayloads(Attribute $taille, Attribute $couleur): array
    {
        $variants = [];

        foreach ($taille->values as $size) {
            foreach ($couleur->values as $color) {
                $variants[] = [
                    'selection' => [
                        ['attribute_id' => $taille->id, 'attribute_value_id' => $size->id],
                        ['attribute_id' => $couleur->id, 'attribute_value_id' => $color->id],
                    ],
                    'sku' => 'PULL-'.strtoupper($size->value).'-'.strtoupper($color->value),
                    'price' => 129.00,
                    'compare_at_price' => 159.00,
                    'cost_price' => 60.00,
                    'manage_stock' => true,
                    'stock_quantity' => 10,
                    'low_stock_threshold' => 3,
                    'weight' => 0.4,
                ];
            }
        }

        return $variants;
    }

    private function productExists(string $slug): bool
    {
        return Product::query()->whereHas('translations', function ($query) use ($slug): void {
            $query->where('locale', 'fr')->where('slug', $slug);
        })->exists();
    }

    private function categoryBySlug(string $slug): ?Category
    {
        return Category::query()->whereHas('translations', function ($query) use ($slug): void {
            $query->where('locale', 'fr')->where('slug', $slug);
        })->first();
    }

    private function brandBySlug(string $slug): ?Brand
    {
        return Brand::query()->whereHas('translations', function ($query) use ($slug): void {
            $query->where('locale', 'fr')->where('slug', $slug);
        })->first();
    }
}
