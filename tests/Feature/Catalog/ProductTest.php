<?php

namespace Tests\Feature\Catalog;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\Role;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Concerns\InteractsWithRoles;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use InteractsWithRoles;
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

    private function brand(): Brand
    {
        return Brand::factory()->create();
    }

    private function category(): Category
    {
        return Category::factory()->create();
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function translations(array $overrides = []): array
    {
        return array_merge([
            'fr' => ['name' => 'Smartphone X Pro', 'slug' => ''],
            'ar' => ['name' => 'هاتف X برو', 'slug' => ''],
            'en' => ['name' => 'Smartphone X Pro', 'slug' => ''],
        ], $overrides);
    }

    private function createSimple(array $attributes = [], array $translations = [], array $options = []): Product
    {
        return $this->service()->create(
            array_merge([
                'type' => ProductType::Simple->value,
                'status' => ProductStatus::Active->value,
                'manage_stock' => true,
                'stock_quantity' => 10,
                'price' => 100,
                'category_ids' => [$this->category()->id],
            ], $attributes),
            $translations !== [] ? $translations : $this->translations(),
            $options,
        );
    }

    public function test_creates_a_simple_product_with_translations(): void
    {
        $product = $this->createSimple();

        $this->assertSame(3, $product->translations()->count());
        $this->assertSame('Smartphone X Pro', $product->translatedName('fr'));
        $this->assertSame('smartphone-x-pro', $product->translation('fr')->slug);
        $this->assertSame('هاتف-x-برو', $product->translation('ar')->slug);
        $this->assertSame('smartphone-x-pro', $product->translation('en')->slug);
        $this->assertTrue($product->isSimple());
        $this->assertFalse($product->isVariable());
    }

    public function test_the_default_locale_translation_is_mandatory(): void
    {
        $this->expectException(ValidationException::class);

        $this->service()->create(['status' => 'active'], [
            'ar' => ['name' => 'هاتف'],
        ]);

        $this->assertSame(0, Product::count());
    }

    public function test_a_missing_translation_falls_back(): void
    {
        $product = $this->service()->create(['status' => 'active'], [
            'fr' => ['name' => 'Électronique', 'slug' => ''],
            'en' => ['name' => 'Electronics', 'slug' => ''],
        ]);

        App::setLocale('ar');

        $this->assertSame('Électronique', $product->translatedName());
        $this->assertSame('Électronique', $product->translatedName('ar'));
        $this->assertSame('Electronics', $product->translatedName('en'));
    }

    public function test_product_is_linked_to_brand_and_categories(): void
    {
        $brand = $this->brand();
        $category = $this->category();

        $product = $this->createSimple([
            'brand_id' => $brand->id,
            'category_ids' => [$category->id],
        ]);

        $this->assertTrue($product->brand->is($brand));
        $this->assertTrue($product->categories->contains($category));
    }

    public function test_initial_stock_movement_is_recorded_for_managed_products(): void
    {
        $product = $this->createSimple(['stock_quantity' => 25]);

        $this->assertSame(1, $product->stockMovements()->count());
        $this->assertSame(25, $product->stockMovements()->first()->quantity);
        $this->assertSame('initial', $product->stockMovements()->first()->type->value);
    }

    public function test_unmanaged_products_do_not_get_an_initial_movement(): void
    {
        $product = $this->createSimple(['manage_stock' => false, 'stock_quantity' => 0]);

        $this->assertSame(0, $product->stockMovements()->count());
    }

    public function test_scopes_filter_active_and_featured_products(): void
    {
        $this->createSimple();
        $this->createSimple(['featured' => true]);
        $this->createSimple(['status' => ProductStatus::Draft->value]);
        $this->createSimple(['status' => ProductStatus::Inactive->value]);

        $this->assertSame(2, Product::active()->count());
        $this->assertSame(1, Product::featured()->count());
    }

    public function test_a_product_sku_is_unique(): void
    {
        $this->createSimple(['sku' => 'X-001']);

        $this->expectException(ValidationException::class);

        $this->createSimple(['sku' => 'X-001']);

        $this->assertSame(1, Product::count());
    }

    public function test_products_are_soft_deleted(): void
    {
        $product = $this->createSimple();

        $product->delete();

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertNull(Product::find($product->id));
    }

    public function test_real_stock_quantity_for_a_simple_product(): void
    {
        $product = $this->createSimple(['stock_quantity' => 40]);

        $this->assertSame(40, $product->realStockQuantity());
    }

    public function test_product_policies_follow_the_granular_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $product = $this->createSimple();

        $viewer = $this->createUserWithRole(Role::Customer->value, ['products.view']);

        $this->assertTrue($viewer->can('viewAny', Product::class));
        $this->assertTrue($viewer->can('view', $product));
        $this->assertFalse($viewer->can('create', Product::class));
        $this->assertFalse($viewer->can('update', $product));
        $this->assertFalse($viewer->can('delete', $product));

        $publisher = $this->createUserWithRole(Role::Customer->value, ['products.publish']);
        $this->assertTrue($publisher->can('publish', $product));

        $customer = $this->createUserWithRole(Role::Customer->value);
        $this->assertFalse($customer->can('viewAny', Product::class));
    }
}
