<?php

namespace Tests\Feature\Catalog;

use App\Enums\AttributeType;
use App\Enums\ContentStatus;
use App\Enums\Role;
use App\Models\Attribute;
use App\Services\AttributeService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Concerns\InteractsWithRoles;
use Tests\TestCase;

class AttributeTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingsSeeder::class);
    }

    private function service(): AttributeService
    {
        return app(AttributeService::class);
    }

    private function translations(array $overrides = []): array
    {
        return array_merge([
            'fr' => ['name' => 'Taille'],
            'ar' => ['name' => 'المقاس'],
            'en' => ['name' => 'Size'],
        ], $overrides);
    }

    private function createAttribute(array $attributes = [], array $values = []): Attribute
    {
        return $this->service()->create(
            array_merge([
                'code' => 'taille',
                'type' => AttributeType::Select->value,
                'status' => ContentStatus::Active->value,
            ], $attributes),
            $this->translations(),
            $values,
        );
    }

    public function test_creates_an_attribute_with_translations(): void
    {
        $attribute = $this->createAttribute();

        $this->assertSame(3, $attribute->translations()->count());
        $this->assertSame('Taille', $attribute->translatedName('fr'));
        $this->assertSame('Size', $attribute->translatedName('en'));
    }

    public function test_the_code_is_required_and_unique(): void
    {
        $this->createAttribute();

        $this->expectException(ValidationException::class);

        $this->createAttribute(['code' => 'taille']);
    }

    public function test_the_default_locale_is_mandatory(): void
    {
        $this->expectException(ValidationException::class);

        $this->service()->create(['code' => 'taille', 'type' => 'select'], [
            'ar' => ['name' => 'المقاس'],
        ]);

        $this->assertSame(0, Attribute::count());
    }

    public function test_values_with_translations_are_persisted(): void
    {
        $attribute = $this->createAttribute([], [
            [
                'value' => 'S',
                'sort_order' => 0,
                'status_is_active' => true,
                'translations' => ['fr' => ['label' => 'S'], 'en' => ['label' => 'S']],
            ],
            [
                'value' => 'M',
                'sort_order' => 1,
                'status_is_active' => true,
                'translations' => ['fr' => ['label' => 'M'], 'en' => ['label' => 'M']],
            ],
        ]);

        $this->assertSame(2, $attribute->values()->count());
        $first = $attribute->values()->first();
        $this->assertSame('S', $first->translatedLabel('fr'));
        $this->assertSame('S', $first->translatedLabel('en'));
    }

    public function test_duplicate_values_are_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->createAttribute([], [
            ['value' => 'S', 'status_is_active' => true],
            ['value' => 'S', 'status_is_active' => true],
        ]);
    }

    public function test_color_values_store_hex_without_the_hash(): void
    {
        $attribute = $this->createAttribute(['type' => AttributeType::Color->value], [
            ['value' => 'Rouge', 'color_code' => '#dc2626', 'status_is_active' => true],
        ]);

        $this->assertSame('dc2626', $attribute->values()->first()->color_code);
    }

    public function test_attribute_policies_follow_the_granular_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $attribute = $this->createAttribute();

        $viewer = $this->createUserWithRole(Role::Customer->value, ['attributes.view']);

        $this->assertTrue($viewer->can('viewAny', Attribute::class));
        $this->assertTrue($viewer->can('view', $attribute));
        $this->assertFalse($viewer->can('create', Attribute::class));
        $this->assertFalse($viewer->can('update', $attribute));
        $this->assertFalse($viewer->can('delete', $attribute));

        $customer = $this->createUserWithRole(Role::Customer->value);
        $this->assertFalse($customer->can('viewAny', Attribute::class));
    }

    public function test_attribute_value_policy_uses_attribute_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $attribute = $this->createAttribute([], [['value' => 'S', 'status_is_active' => true]]);
        $value = $attribute->values()->first();

        $editor = $this->createUserWithRole(Role::Customer->value, ['attributes.update']);
        $this->assertTrue($editor->can('update', $value));
    }
}
