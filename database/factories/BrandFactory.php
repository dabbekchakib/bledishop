<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\Brand;
use App\Models\BrandTranslation;
use App\Support\Slugger;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => ContentStatus::Active->value,
            'sort_order' => 0,
            'is_featured' => false,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Brand $brand): void {
            $base = $this->faker->unique()->company;

            foreach (['fr', 'ar', 'en'] as $locale) {
                $name = match ($locale) {
                    'ar' => 'علامة '.$this->faker->numberBetween(1, 999),
                    'en' => $base.' EN',
                    default => $base,
                };

                BrandTranslation::firstOrCreate(
                    ['brand_id' => $brand->id, 'locale' => $locale],
                    [
                        'name' => $name,
                        'slug' => Slugger::unique(Slugger::make($name, $locale), $locale, 'brand_translations'),
                    ],
                );
            }
        });
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['status' => ContentStatus::Inactive->value]);
    }
}
