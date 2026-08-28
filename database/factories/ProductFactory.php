<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductTranslation;
use App\Support\Slugger;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'brand_id' => Brand::factory(),
            'type' => ProductType::Simple->value,
            'status' => ProductStatus::Active->value,
            'featured' => false,
            'sku' => 'SKU-'.strtoupper($this->faker->unique()->bothify('###-???')),
            'price' => $this->faker->randomFloat(2, 5, 500),
            'compare_at_price' => null,
            'cost_price' => null,
            'manage_stock' => true,
            'stock_quantity' => $this->faker->numberBetween(0, 100),
            'low_stock_threshold' => 5,
            'stock_status' => null,
            'weight' => $this->faker->optional()->randomFloat(2, 0.1, 20),
            'length' => null,
            'width' => null,
            'height' => null,
            'published_at' => now(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Product $product): void {
            $base = $this->faker->unique()->words(3, true);

            foreach (['fr', 'ar', 'en'] as $locale) {
                $name = $this->localizedProductName($base, $locale);

                ProductTranslation::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'locale' => $locale,
                    ],
                    [
                        'name' => $name,
                        'slug' => Slugger::unique(Slugger::make($name, $locale), $locale, 'product_translations'),
                        'short_description' => $this->faker->sentence,
                        'description' => $this->faker->paragraphs(2, true),
                    ],
                );
            }
        });
    }

    public function simple(): static
    {
        return $this->state(fn (): array => ['type' => ProductType::Simple->value]);
    }

    public function variable(): static
    {
        return $this->state(fn (): array => ['type' => ProductType::Variable->value]);
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['status' => ProductStatus::Draft->value]);
    }

    public function featured(): static
    {
        return $this->state(fn (): array => ['featured' => true]);
    }

    public function withoutStockManagement(): static
    {
        return $this->state(fn (): array => ['manage_stock' => false]);
    }

    private function localizedProductName(string $base, string $locale): string
    {
        return match ($locale) {
            'ar' => 'منتج '.$this->faker->numberBetween(1, 9999),
            'en' => ucfirst($base).' (EN)',
            default => ucfirst($base),
        };
    }
}
