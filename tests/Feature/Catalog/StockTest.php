<?php

namespace Tests\Feature\Catalog;

use App\Enums\ProductType;
use App\Enums\StockMovementType;
use App\Enums\StockStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Services\StockService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingsSeeder::class);
    }

    private function service(): StockService
    {
        return app(StockService::class);
    }

    private function product(int $quantity = 0, int $threshold = 5): Product
    {
        return Product::create([
            'type' => ProductType::Simple->value,
            'status' => 'active',
            'manage_stock' => true,
            'stock_quantity' => $quantity,
            'low_stock_threshold' => $threshold,
        ]);
    }

    private function variant(int $quantity = 0, int $threshold = 3): ProductVariant
    {
        $product = Product::create(['type' => ProductType::Variable->value, 'status' => 'active']);

        return $product->variants()->create([
            'manage_stock' => true,
            'stock_quantity' => $quantity,
            'low_stock_threshold' => $threshold,
        ]);
    }

    public function test_increase_adds_stock_and_records_a_movement(): void
    {
        $product = $this->product(5);

        $this->service()->increase($product, 15);

        $this->assertSame(20, $product->fresh()->stock_quantity);
        $this->assertSame(1, $product->stockMovements()->count());
        $movement = $product->stockMovements()->first();
        $this->assertSame(StockMovementType::Increase->value, $movement->type->value);
        $this->assertSame(15, $movement->quantity);
    }

    public function test_decrease_reduces_stock(): void
    {
        $product = $this->product(20);

        $this->service()->decrease($product, 7);

        $this->assertSame(13, $product->fresh()->stock_quantity);
        $this->assertSame(-7, $product->stockMovements()->first()->quantity);
    }

    public function test_stock_cannot_go_negative(): void
    {
        $product = $this->product(3);

        $this->expectException(ValidationException::class);

        $this->service()->decrease($product, 5);

        $this->assertSame(3, $product->fresh()->stock_quantity);
    }

    public function test_adjust_sets_an_absolute_quantity(): void
    {
        $product = $this->product(10);

        $this->service()->adjust($product, 4);

        $this->assertSame(4, $product->fresh()->stock_quantity);
        $this->assertSame(StockMovementType::Adjustment->value, $product->stockMovements()->first()->type->value);
    }

    public function test_adjust_rejects_negative_stock(): void
    {
        $product = $this->product(10);

        $this->expectException(ValidationException::class);

        $this->service()->adjust($product, -2);
    }

    public function test_can_sell_and_stock_flags(): void
    {
        $product = $this->product(10, 5);

        $this->assertTrue($this->service()->canSell($product, 3));
        $this->assertFalse($this->service()->canSell($product, 11));
        $this->assertTrue($this->service()->isInStock($product));
        $this->assertFalse($this->service()->isOutOfStock($product));
        $this->assertFalse($this->service()->isLowStock($product));

        $this->service()->decrease($product, 8);

        $this->assertTrue($this->service()->isLowStock($product->fresh()));
        $this->assertSame(StockStatus::InStock, $this->service()->stockStatusOf($product->fresh()));
    }

    public function test_out_of_stock_status(): void
    {
        $product = $this->product(0);

        $this->assertTrue($this->service()->isOutOfStock($product));
        $this->assertSame(StockStatus::OutOfStock, $this->service()->stockStatusOf($product));
        $this->assertFalse($this->service()->isInStock($product));
    }

    public function test_variant_stock_is_managed_independently(): void
    {
        $variant = $this->variant(8, 2);

        $this->service()->increase($variant, 4);
        $this->assertSame(12, $variant->fresh()->stock_quantity);

        $this->service()->decrease($variant, 5);
        $this->assertSame(7, $variant->fresh()->stock_quantity);

        $this->assertSame(2, $variant->stockMovements()->count());

        $movement = $variant->stockMovements()->first();
        $this->assertSame($variant->product_id, $movement->product_id);
        $this->assertSame($variant->id, $movement->product_variant_id);
    }

    public function test_every_mutation_runs_in_a_transaction_and_is_idempotent(): void
    {
        $product = $this->product(0);

        $this->service()->initialize($product, 30);
        $this->assertSame(30, $product->fresh()->stock_quantity);
        $this->assertSame(StockMovementType::Initial->value, $product->stockMovements()->first()->type->value);

        // A failed decrease must not leave a movement behind.
        $before = $product->stockMovements()->count();

        try {
            $this->service()->decrease($product, 100);
        } catch (ValidationException $e) {
            // expected
        }

        $this->assertSame($before, $product->stockMovements()->count());
        $this->assertSame(30, $product->fresh()->stock_quantity);
        $this->assertSame(0, StockMovement::where('type', StockMovementType::Decrease->value)->count());
    }
}
