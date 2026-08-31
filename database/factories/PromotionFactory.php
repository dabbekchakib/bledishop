<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Promotion>
 */
class PromotionFactory extends Factory
{
    protected $model = Promotion::class;

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
            'is_flash_sale' => false,
            'is_countdown' => false,
            'countdown_title' => null,
            'active' => true,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(7),
            'product_ids' => [],
            'category_ids' => [],
            'brand_ids' => [],
            'promo_quantity' => null,
            'promo_quantity_used' => 0,
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

    public function promoPrice(float $value = 5): static
    {
        return $this->state(fn (): array => ['type' => DiscountType::PromoPrice, 'value' => $value]);
    }

    public function disabled(): static
    {
        return $this->state(fn (): array => ['active' => false]);
    }
}
