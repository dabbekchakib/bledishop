<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\InteractsWithRoles;
use Tests\TestCase;

class MarketingAccessTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    private function marketingUrls(): array
    {
        return [
            '/admin/coupons',
            '/admin/discount-rules',
            '/admin/promotions',
            '/admin/campaigns',
            '/admin/banners',
            '/admin/marketing',
        ];
    }

    public function test_super_admin_can_access_all_marketing_pages(): void
    {
        $admin = $this->createUserWithRole(Role::SuperAdmin->value);

        foreach ($this->marketingUrls() as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_user_without_marketing_permission_is_forbidden(): void
    {
        $user = User::factory()->create();

        foreach ($this->marketingUrls() as $url) {
            $this->actingAs($user)->get($url)->assertForbidden();
        }
    }

    public function test_staff_role_can_view_coupons_but_not_create(): void
    {
        $staff = $this->createUserWithRole(Role::Staff->value);

        $this->assertTrue($staff->can('coupons.view'));
        $this->assertFalse($staff->can('coupons.create'));

        $this->actingAs($staff)->get('/admin/marketing')->assertOk();
        $this->actingAs($staff)->get('/admin/coupons')->assertOk();
        $this->actingAs($staff)->get('/admin/coupons/create')->assertForbidden();
    }

    public function test_manager_role_gets_marketing_view_and_manage_permissions(): void
    {
        $manager = $this->createUserWithRole(Role::Manager->value);

        $this->assertTrue($manager->can('coupons.view'));
        $this->assertTrue($manager->can('coupons.create'));
        $this->assertTrue($manager->can('coupons.update'));
        $this->assertFalse($manager->can('coupons.delete'));

        $this->assertTrue($manager->can('promotions.view'));
        $this->assertTrue($manager->can('promotions.update'));
        $this->assertFalse($manager->can('promotions.delete'));

        $this->actingAs($manager)->get('/admin/marketing')->assertOk();
        $this->actingAs($manager)->get('/admin/coupons')->assertOk();
    }

    public function test_super_admin_can_open_all_marketing_resources_list_and_create_pages(): void
    {
        $admin = $this->createUserWithRole(Role::SuperAdmin->value);

        $entities = ['coupons', 'discount-rules', 'promotions', 'campaigns', 'banners'];

        foreach ($entities as $entity) {
            $this->actingAs($admin)->get('/admin/'.$entity)->assertOk();
            $this->actingAs($admin)->get('/admin/'.$entity.'/create')->assertOk();
        }
    }
}
