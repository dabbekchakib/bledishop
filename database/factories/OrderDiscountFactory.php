<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderDiscount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderDiscount>
 */
class OrderDiscountFactory extends Factory
{
    protected $model = OrderDiscount::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'discountable_type' => Coupon::class,
            'discountable_id' => Coupon::factory(),
            'kind' => 'coupon',
            'code' => null,
            'name' => $this->faker->words(2, true),
            'type' => DiscountType::Percentage,
            'value' => 10,
            'amount' => 500,
        ];
    }
}
