<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\Product;
use App\Services\ProductService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\InteractsWithRoles;
use Tests\TestCase;

class ProductAdminTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingsSeeder::class);
    }

    private function createProduct(): Product
    {
        return app(ProductService::class)->create(
            ['type' => 'simple', 'status' => 'active', 'manage_stock' => true, 'stock_quantity' => 5, 'price' => 100],
            [
                'fr' => ['name' => 'Produit A', 'slug' => 'produit-a'],
                'ar' => ['name' => 'منتج أ', 'slug' => ''],
                'en' => ['name' => 'Product A', 'slug' => ''],
            ],
        );
    }

    public function test_a_manager_can_list_products(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->createProduct();

        $manager = $this->createUserWithRole(Role::Manager->value);
        $this->actingAs($manager)->get('/admin/products')->assertSuccessful();
    }

    public function test_a_manager_with_create_permission_can_open_the_create_page(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $creator = $this->createUserWithRole(Role::Manager->value, ['products.create']);
        $this->actingAs($creator)->get('/admin/products/create')->assertSuccessful();
    }

    public function test_a_manager_can_open_the_edit_page(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $product = $this->createProduct();

        $editor = $this->createUserWithRole(Role::Manager->value);
        $this->actingAs($editor)->get("/admin/products/{$product->id}/edit")->assertSuccessful();
    }

    public function test_staff_without_permission_is_rejected_from_products(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $product = $this->createProduct();

        $staff = $this->createUserWithRole(Role::Staff->value);

        $this->actingAs($staff)->get('/admin/products')->assertForbidden();
        $this->actingAs($staff)->get('/admin/products/create')->assertForbidden();
        $this->actingAs($staff)->get("/admin/products/{$product->id}/edit")->assertForbidden();
    }

    public function test_a_manager_can_list_attributes(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $manager = $this->createUserWithRole(Role::Manager->value);
        $this->actingAs($manager)->get('/admin/attributes')->assertSuccessful();
    }

    public function test_staff_is_rejected_from_attributes(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $staff = $this->createUserWithRole(Role::Staff->value);

        $this->actingAs($staff)->get('/admin/attributes')->assertForbidden();
        $this->actingAs($staff)->get('/admin/attributes/create')->assertForbidden();
    }
}
