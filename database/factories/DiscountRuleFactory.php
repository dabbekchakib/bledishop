<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Models\DiscountRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscountRule>
 */
class DiscountRuleFactory extends Factory
{
    protected $model = DiscountRule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'type' => DiscountType::Percentage,
            'value' => 10,
            'priority' => 0,
            'cumulative' => false,
            'active' => true,
            'min_subtotal' => null,
            'min_quantity' => null,
            'min_items' => null,
            'product_ids' => [],
            'category_ids' => [],
            'brand_ids' => [],
            'customer_ids' => [],
            'first_purchase_only' => false,
            'starts_at' => null,
            'ends_at' => null,
        ];
    }

    public function percentage(float $value = 10): static
    {
        return $this->state(fn (): array => ['type' => DiscountType::Percentage, 'value' => $value]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['active' => false]);
    }
}
