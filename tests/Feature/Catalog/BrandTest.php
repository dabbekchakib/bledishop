<?php

namespace Tests\Feature\Catalog;

use App\Enums\ContentStatus;
use App\Enums\Role;
use App\Models\Brand;
use App\Services\BrandService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Concerns\InteractsWithRoles;
use Tests\TestCase;

class BrandTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingsSeeder::class);
    }

    private function service(): BrandService
    {
        return app(BrandService::class);
    }

    private function createBrand(array $attributes = []): Brand
    {
        return $this->service()->create(array_merge([
            'status' => ContentStatus::Active->value,
            'sort_order' => 0,
            'is_featured' => false,
        ], $attributes), [
            'fr' => ['name' => 'Apple', 'slug' => ''],
            'ar' => ['name' => 'آبل', 'slug' => ''],
            'en' => ['name' => 'Apple', 'slug' => ''],
        ]);
    }

    public function test_creates_a_brand_with_all_translations_and_slugs(): void
    {
        $brand = $this->createBrand();

        $this->assertDatabaseHas('brands', ['id' => $brand->id, 'status' => ContentStatus::Active->value]);
        $this->assertSame(3, $brand->translations()->count());

        $this->assertSame('Apple', $brand->translation('fr')->name);
        $this->assertSame('apple', $brand->translation('fr')->slug);
        $this->assertSame('آبل', $brand->translation('ar')->slug);
        $this->assertSame('apple', $brand->translation('en')->slug);
    }

    public function test_the_default_locale_translation_is_mandatory(): void
    {
        $this->expectException(ValidationException::class);

        $this->service()->create(['status' => 'active'], [
            'en' => ['name' => 'Apple'],
        ]);

        $this->assertSame(0, Brand::count());
    }

    public function test_a_missing_translation_falls_back_to_the_default_locale(): void
    {
        $brand = $this->service()->create(['status' => 'active'], [
            'fr' => ['name' => 'Apple', 'slug' => ''],
            'ar' => ['name' => 'آبل', 'slug' => ''],
        ]);

        App::setLocale('en');

        $this->assertSame('Apple', $brand->translatedName());
        $this->assertSame('آبل', $brand->translatedName('ar'));
        $this->assertSame('Apple', $brand->translatedName('en'));
    }

    public function test_duplicate_slugs_are_suffixed_per_locale(): void
    {
        $first = $this->createBrand();
        $second = $this->createBrand();

        $this->assertSame('apple', $first->translation('fr')->slug);
        $this->assertSame('apple-2', $second->translation('fr')->slug);
    }

    public function test_inactive_brands_are_excluded_from_the_public_scope(): void
    {
        $this->createBrand();
        $this->service()->create(['status' => 'inactive'], [
            'fr' => ['name' => 'Samsung', 'slug' => ''],
            'ar' => ['name' => 'سامسونج', 'slug' => ''],
            'en' => ['name' => 'Samsung', 'slug' => ''],
        ]);

        $this->assertSame(1, Brand::public()->count());
    }

    public function test_brand_policies_follow_the_granular_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $viewer = $this->createUserWithRole(Role::Customer->value, ['brands.view']);
        $brand = $this->createBrand();

        $this->assertTrue($viewer->can('viewAny', Brand::class));
        $this->assertTrue($viewer->can('view', $brand));
        $this->assertFalse($viewer->can('create', Brand::class));
        $this->assertFalse($viewer->can('delete', $brand));
    }

    public function test_brand_resources_are_protected_by_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $staff = $this->createUserWithRole(Role::Staff->value);
        $brand = $this->createBrand();

        $manager = $this->createUserWithRole(Role::Manager->value);
        $this->actingAs($manager)->get('/admin/brands')->assertSuccessful();

        $creator = $this->createUserWithRole(Role::Manager->value, ['brands.create']);
        $this->actingAs($creator)->get('/admin/brands/create')->assertSuccessful();

        $this->actingAs($staff)->get('/admin/brands')->assertForbidden();
        $this->actingAs($staff)->get('/admin/brands/'.$brand->id.'/edit')->assertForbidden();
        $this->actingAs($staff)->get('/admin/brands/create')->assertForbidden();
    }
}
