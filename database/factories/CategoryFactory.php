<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Support\Slugger;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parent_id' => null,
            'status' => ContentStatus::Active->value,
            'sort_order' => 0,
            'is_featured' => false,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Category $category): void {
            $base = $this->faker->unique()->words(2, true);

            foreach (['fr', 'ar', 'en'] as $locale) {
                $name = match ($locale) {
                    'ar' => 'فئة '.$this->faker->numberBetween(1, 999),
                    'en' => ucfirst($base).' EN',
                    default => ucfirst($base),
                };

                CategoryTranslation::firstOrCreate(
                    ['category_id' => $category->id, 'locale' => $locale],
                    [
                        'name' => $name,
                        'slug' => Slugger::unique(Slugger::make($name, $locale), $locale, 'category_translations'),
                    ],
                );
            }
        });
    }

    public function child(): static
    {
        return $this->state(fn (): array => ['parent_id' => Category::factory()]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['status' => ContentStatus::Inactive->value]);
    }
}
