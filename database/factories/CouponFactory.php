<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->bothify('PROMO-####')),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'type' => DiscountType::Percentage,
            'value' => 10,
            'min_subtotal' => null,
            'max_subtotal' => null,
            'usage_limit' => null,
            'per_customer_limit' => null,
            'product_ids' => [],
            'category_ids' => [],
            'brand_ids' => [],
            'excluded_product_ids' => [],
            'excluded_category_ids' => [],
            'cumulative' => false,
            'active' => true,
            'starts_at' => null,
            'ends_at' => null,
            'usage_count' => 0,
        ];
    }

    public function percentage(float $value = 10): static
    {
        return $this->state(fn (): array => ['type' => DiscountType::Percentage, 'value' => $value]);
    }

    public function fixed(float $value = 15): static
    {
        return $this->state(fn (): array => ['type' => DiscountType::Fixed, 'value' => $value]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['active' => false]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => ['ends_at' => now()->subDay()]);
    }
}
