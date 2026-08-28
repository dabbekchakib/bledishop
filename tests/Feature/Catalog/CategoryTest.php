<?php

namespace Tests\Feature\Catalog;

use App\Enums\ContentStatus;
use App\Enums\Role;
use App\Models\Category;
use App\Services\CategoryService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Concerns\InteractsWithRoles;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingsSeeder::class);
    }

    private function service(): CategoryService
    {
        return app(CategoryService::class);
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function translations(array $overrides = []): array
    {
        return array_merge([
            'fr' => ['name' => 'Électronique', 'slug' => ''],
            'ar' => ['name' => 'الإلكترونيات', 'slug' => ''],
            'en' => ['name' => 'Electronics', 'slug' => ''],
        ], $overrides);
    }

    private function createCategory(?int $parentId = null, array $translations = []): Category
    {
        return $this->service()->create([
            'parent_id' => $parentId,
            'status' => 'active',
            'sort_order' => 0,
            'is_featured' => false,
        ], $translations !== [] ? $translations : $this->translations());
    }

    public function test_creates_a_category_with_all_translations_and_slugs(): void
    {
        $category = $this->createCategory();

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'status' => ContentStatus::Active->value]);
        $this->assertSame(3, $category->translations()->count());

        $this->assertSame('Électronique', $category->translation('fr')->name);
        $this->assertSame('electronique', $category->translation('fr')->slug);
        $this->assertSame('الإلكترونيات', $category->translation('ar')->slug);
        $this->assertSame('electronics', $category->translation('en')->slug);
    }

    public function test_the_default_locale_translation_is_mandatory(): void
    {
        $this->expectException(ValidationException::class);

        $this->service()->create(['status' => 'active'], [
            'ar' => ['name' => 'الإلكترونيات'],
        ]);

        $this->assertSame(0, Category::count());
    }

    public function test_a_missing_translation_falls_back_to_the_default_locale(): void
    {
        $category = $this->service()->create(['status' => 'active'], [
            'fr' => ['name' => 'Électronique', 'slug' => ''],
            'en' => ['name' => 'Electronics', 'slug' => ''],
        ]);

        App::setLocale('ar');

        $this->assertSame('Électronique', $category->translatedName());
        $this->assertSame('Électronique', $category->translatedName('ar'));
        $this->assertSame('Electronics', $category->translatedName('en'));
    }

    public function test_a_custom_slug_is_preserved_while_an_empty_one_is_regenerated(): void
    {
        $category = $this->createCategory();

        $this->service()->update($category, ['status' => 'active'], [
            'fr' => ['name' => 'Smartphones', 'slug' => 'telephones-mobiles'],
            'ar' => ['name' => 'Smartphones', 'slug' => ''],
            'en' => ['name' => 'Smartphones', 'slug' => ''],
        ]);

        $category->refresh();

        $this->assertSame('Smartphones', $category->translatedName('fr'));
        $this->assertSame('telephones-mobiles', $category->translation('fr')->slug);
        $this->assertSame('smartphones', $category->translation('en')->slug);
    }

    public function test_a_category_cannot_be_its_own_parent(): void
    {
        $category = $this->createCategory();

        $this->expectException(ValidationException::class);

        $this->service()->update($category, ['parent_id' => $category->id], $this->translations());
    }

    public function test_hierarchy_cycles_are_rejected(): void
    {
        $root = $this->createCategory();
        $branch = $this->createCategory($root->id);
        $leaf = $this->createCategory($branch->id);

        $this->expectException(ValidationException::class);

        $this->service()->update($root, ['parent_id' => $leaf->id], $this->translations());
    }

    public function test_descendant_ids_follow_the_hierarchy(): void
    {
        $root = $this->createCategory();
        $branch = $this->createCategory($root->id);
        $leaf = $this->createCategory($branch->id);

        $this->assertSame([$branch->id, $leaf->id], $root->descendantIds());
    }

    public function test_children_are_linked_to_their_parent(): void
    {
        $root = $this->createCategory();
        $child = $this->createCategory($root->id);

        $this->assertSame($root->id, $child->parent_id);
        $this->assertTrue($child->parent->is($root));
        $this->assertTrue($root->children->contains($child));
    }

    public function test_deleting_a_category_promotes_its_children_to_roots(): void
    {
        $root = $this->createCategory();
        $child = $this->createCategory($root->id);

        $root->delete();

        $this->assertSoftDeleted('categories', ['id' => $root->id]);

        $child->refresh();

        $this->assertNull($child->parent_id);
        $this->assertFalse($child->trashed());
    }

    public function test_duplicate_slugs_are_suffixed_per_locale(): void
    {
        $first = $this->service()->create(['status' => 'active'], [
            'fr' => ['name' => 'Phones', 'slug' => ''],
        ]);
        $second = $this->service()->create(['status' => 'active'], [
            'fr' => ['name' => 'Phones', 'slug' => ''],
        ]);

        $this->assertSame('phones', $first->translation('fr')->slug);
        $this->assertSame('phones-2', $second->translation('fr')->slug);
    }

    public function test_inactive_categories_are_excluded_from_the_public_scope(): void
    {
        $this->createCategory();
        $this->service()->create(['status' => 'inactive'], $this->translations());

        $this->assertSame(1, Category::public()->count());
    }

    public function test_the_featured_scope(): void
    {
        $this->service()->create(['status' => 'active', 'is_featured' => true], $this->translations());
        $this->createCategory();

        $this->assertSame(1, Category::featured()->count());
    }

    public function test_category_policies_follow_the_granular_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $viewer = $this->createUserWithRole(Role::Customer->value, ['categories.view']);
        $category = $this->createCategory();

        $this->assertTrue($viewer->can('viewAny', Category::class));
        $this->assertTrue($viewer->can('view', $category));
        $this->assertFalse($viewer->can('create', Category::class));
        $this->assertFalse($viewer->can('update', $category));
        $this->assertFalse($viewer->can('delete', $category));

        $customer = $this->createUserWithRole(Role::Customer->value);

        $this->assertFalse($customer->can('viewAny', Category::class));
    }

    public function test_category_resources_are_protected_by_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $staff = $this->createUserWithRole(Role::Staff->value);
        $category = $this->createCategory();

        $manager = $this->createUserWithRole(Role::Manager->value);
        $this->actingAs($manager)->get('/admin/categories')->assertSuccessful();

        $creator = $this->createUserWithRole(Role::Manager->value, ['categories.create']);
        $this->actingAs($creator)->get('/admin/categories/create')->assertSuccessful();

        $this->actingAs($staff)->get('/admin/categories')->assertForbidden();
        $this->actingAs($staff)->get('/admin/categories/'.$category->id.'/edit')->assertForbidden();
        $this->actingAs($staff)->get('/admin/categories/create')->assertForbidden();
    }
}
