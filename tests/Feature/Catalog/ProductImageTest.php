<?php

namespace Tests\Feature\Catalog;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Product;
use App\Services\ProductService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingsSeeder::class);
    }

    private function service(): ProductService
    {
        return app(ProductService::class);
    }

    private function createProduct(array $options): Product
    {
        return $this->service()->create(
            [
                'type' => ProductType::Simple->value,
                'status' => ProductStatus::Active->value,
                'manage_stock' => true,
                'stock_quantity' => 5,
            ],
            [
                'fr' => ['name' => 'Produit', 'slug' => 'produit'],
                'ar' => ['name' => 'منتج', 'slug' => ''],
                'en' => ['name' => 'Product', 'slug' => ''],
            ],
            $options,
        );
    }

    public function test_images_are_persisted_with_primary_flag(): void
    {
        $product = $this->createProduct([
            'images' => [
                ['path' => 'catalog/products/a.jpg', 'is_primary' => false, 'alt' => 'A'],
                ['path' => 'catalog/products/b.jpg', 'is_primary' => true, 'alt' => 'B'],
                ['path' => 'catalog/products/c.jpg', 'is_primary' => false, 'alt' => 'C'],
            ],
        ]);

        $this->assertSame(3, $product->images()->count());
        $this->assertSame(1, $product->images()->where('is_primary', true)->count());
        $this->assertSame('catalog/products/b.jpg', $product->primaryImage()->first()->path);
    }

    public function test_replacing_images_drops_the_previous_gallery(): void
    {
        $product = $this->createProduct([
            'images' => [
                ['path' => 'catalog/products/a.jpg', 'is_primary' => true],
            ],
        ]);

        $this->service()->update($product, ['status' => ProductStatus::Active->value], [
            'fr' => ['name' => 'Produit', 'slug' => 'produit'],
            'ar' => ['name' => 'منتج', 'slug' => ''],
            'en' => ['name' => 'Product', 'slug' => ''],
        ], [
            'images' => [
                ['path' => 'catalog/products/d.jpg', 'is_primary' => true],
            ],
        ]);

        $this->assertSame(1, $product->images()->count());
        $this->assertSame('catalog/products/d.jpg', $product->images()->first()->path);
    }

    public function test_an_explicit_primary_is_guaranteed_when_missing(): void
    {
        $product = $this->createProduct([
            'images' => [
                ['path' => 'catalog/products/a.jpg', 'is_primary' => false],
                ['path' => 'catalog/products/b.jpg', 'is_primary' => false],
            ],
        ]);

        $this->service()->ensurePrimaryImage($product);

        $this->assertSame(1, $product->images()->where('is_primary', true)->count());
    }

    public function test_images_are_deleted_with_the_product(): void
    {
        $product = $this->createProduct([
            'images' => [
                ['path' => 'catalog/products/a.jpg', 'is_primary' => true],
            ],
        ]);

        $this->assertSame(1, $product->images()->count());

        $product->forceDelete();

        $this->assertSame(0, $product->images()->count());
    }
}
