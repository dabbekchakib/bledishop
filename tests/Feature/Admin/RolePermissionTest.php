<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\Feature\Concerns\InteractsWithRoles;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    public function test_seeded_permissions_and_roles_exist(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->assertSame(12, Permission::count());
        $this->assertSame(5, SpatieRole::count());

        $superAdmin = SpatieRole::findByName(Role::SuperAdmin->value);
        $this->assertCount(12, $superAdmin->permissions);

        $staff = SpatieRole::findByName(Role::Staff->value);
        $this->assertTrue($staff->hasPermissionTo('users.view'));
        $this->assertFalse($staff->hasPermissionTo('users.delete'));
    }

    public function test_a_role_can_be_created_with_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $role = $this->createRole('editor', ['users.view', 'users.update']);

        $this->assertTrue($role->hasPermissionTo('users.view'));
        $this->assertTrue($role->hasPermissionTo('users.update'));

        $user = $this->createUserWithRole('editor');

        $this->assertTrue($user->can('users.view'));
        $this->assertTrue($user->can('users.update'));
        $this->assertFalse($user->can('users.delete'));
    }

    public function test_user_without_permission_is_denied(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $customer = $this->createUserWithRole(Role::Customer->value);

        $this->assertFalse($customer->can('users.view'));
        $this->assertFalse($customer->can('users.delete'));
        $this->assertFalse($customer->can('roles.view'));
    }

    public function test_permissions_can_be_granted_directly_to_a_user(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = $this->createUserWithRole(Role::Customer->value, ['users.view']);

        $this->assertTrue($user->can('users.view'));
        $this->assertFalse($user->can('users.create'));
    }

    public function test_super_admin_and_admin_have_full_access(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = $this->createUserWithRole(Role::Admin->value);
        $this->assertTrue($admin->can('users.create'));
        $this->assertTrue($admin->can('users.delete'));
        $this->assertTrue($admin->can('roles.update'));
        $this->assertTrue($admin->can('permissions.view'));
    }

    public function test_the_super_admin_role_cannot_be_deleted_by_a_non_super_admin(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = $this->createUserWithRole(Role::Admin->value);
        $superAdminRole = SpatieRole::findByName(Role::SuperAdmin->value);

        $this->assertFalse($admin->can('delete', $superAdminRole));
        $this->assertTrue($admin->can('delete', SpatieRole::findByName(Role::Staff->value)));
    }

    public function test_the_super_admin_role_cannot_be_updated_by_a_non_super_admin(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = $this->createUserWithRole(Role::Admin->value);
        $superAdminRole = SpatieRole::findByName(Role::SuperAdmin->value);

        $this->assertFalse($admin->can('update', $superAdminRole));
    }

    public function test_resource_url_is_protected_without_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $staff = $this->createUserWithRole(Role::Staff->value);

        $this->actingAs($staff)->get('/admin/roles')->assertForbidden();
        $this->actingAs($staff)->get('/admin/permissions')->assertForbidden();
    }
}
