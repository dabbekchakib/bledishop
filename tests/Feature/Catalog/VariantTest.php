<?php

namespace Tests\Feature\Catalog;

use App\Enums\AttributeType;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Services\ProductService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class VariantTest extends TestCase
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

    private function attribute(string $code = 'taille', string $type = AttributeType::Select->value): Attribute
    {
        $a = Attribute::create(['code' => $code, 'type' => $type, 'status' => 'active']);

        $a->translations()->create(['locale' => 'fr', 'name' => 'Taille']);

        return $a;
    }

    private function value(Attribute $attribute, string $value): AttributeValue
    {
        $v = $attribute->values()->create(['value' => $value, 'status' => 'active']);

        $v->translations()->create(['locale' => 'fr', 'label' => $value]);

        return $v;
    }

    private function createVariableProduct(Attribute $size, array $variantSelections): Product
    {
        return $this->service()->create(
            [
                'type' => ProductType::Variable->value,
                'status' => ProductStatus::Active->value,
                'attribute_ids' => [$size->id],
            ],
            [
                'fr' => ['name' => 'Pull', 'slug' => ''],
                'ar' => ['name' => 'كنزة', 'slug' => ''],
                'en' => ['name' => 'Sweater', 'slug' => ''],
            ],
            [
                'variants' => $variantSelections,
            ],
        );
    }

    private function selectionRow(Attribute $attribute, AttributeValue $value): array
    {
        return ['attribute_id' => $attribute->id, 'attribute_value_id' => $value->id];
    }

    public function test_a_variable_product_creates_variants_with_combinations(): void
    {
        $size = $this->attribute();
        $s = $this->value($size, 'S');
        $m = $this->value($size, 'M');

        $product = $this->createVariableProduct($size, [
            ['selection' => [$this->selectionRow($size, $s)], 'sku' => 'P-S', 'price' => 100, 'stock_quantity' => 5],
            ['selection' => [$this->selectionRow($size, $m)], 'sku' => 'P-M', 'price' => 110, 'stock_quantity' => 7],
        ]);

        $this->assertTrue($product->isVariable());
        $this->assertSame(2, $product->variants()->count());
        $this->assertSame(12, $product->realStockQuantity());

        $variant = $product->variants()->where('sku', 'P-S')->first();
        $this->assertSame($s->id, $variant->variantValues()->first()->attribute_value_id);
    }

    public function test_the_same_combination_cannot_exist_twice_in_a_product(): void
    {
        $size = $this->attribute();
        $s = $this->value($size, 'S');

        $this->expectException(ValidationException::class);

        $this->createVariableProduct($size, [
            ['selection' => [$this->selectionRow($size, $s)]],
            ['selection' => [$this->selectionRow($size, $s)]],
        ]);

        $this->assertSame(0, Product::count());
    }

    public function test_a_variant_requires_at_least_one_attribute(): void
    {
        $size = $this->attribute();

        $this->expectException(ValidationException::class);

        $this->createVariableProduct($size, [
            ['selection' => []],
        ]);
    }

    public function test_variant_skus_are_unique_across_products(): void
    {
        $size = $this->attribute('taille');
        $s = $this->value($size, 'S');

        $this->createVariableProduct($size, [
            ['selection' => [$this->selectionRow($size, $s)], 'sku' => 'P-UNIQUE'],
        ]);

        $size2 = $this->attribute('couleur', AttributeType::Color->value);
        $red = $this->value($size2, 'Rouge');

        $this->expectException(ValidationException::class);

        $this->createVariableProduct($size2, [
            ['selection' => [$this->selectionRow($size2, $red)], 'sku' => 'P-UNIQUE'],
        ]);
    }

    public function test_replacing_variants_removes_old_ones(): void
    {
        $size = $this->attribute();
        $s = $this->value($size, 'S');
        $m = $this->value($size, 'M');

        $product = $this->createVariableProduct($size, [
            ['selection' => [$this->selectionRow($size, $s)], 'sku' => 'P-S'],
        ]);

        $this->assertSame(1, $product->variants()->count());

        $this->service()->update($product, ['type' => ProductType::Variable->value], [
            'fr' => ['name' => 'Pull', 'slug' => ''],
            'ar' => ['name' => 'كنزة', 'slug' => ''],
            'en' => ['name' => 'Sweater', 'slug' => ''],
        ], [
            'variants' => [
                ['selection' => [$this->selectionRow($size, $m)], 'sku' => 'P-M'],
            ],
        ]);

        $this->assertSame(1, $product->variants()->count());
        $this->assertSame('P-M', $product->variants()->first()->sku);
    }

    public function test_variant_combination_label_is_readable(): void
    {
        $size = $this->attribute();
        $m = $this->value($size, 'M');

        $product = $this->createVariableProduct($size, [
            ['selection' => [$this->selectionRow($size, $m)], 'sku' => 'P-M'],
        ]);

        $variant = $product->variants()->first();
        $this->assertSame('M', $variant->combinationLabel());
    }
}
