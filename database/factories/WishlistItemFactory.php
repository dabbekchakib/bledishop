<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WishlistItem>
 */
class WishlistItemFactory extends Factory
{
    protected $model = WishlistItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'session_id' => null,
        ];
    }

    public function forSession(string $sessionId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'session_id' => $sessionId,
        ]);
    }
}
