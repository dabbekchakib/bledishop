<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Concerns\InteractsWithRoles;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    public function test_staff_with_view_permission_can_access_users_list(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $staff = $this->createUserWithRole(Role::Staff->value);
        User::factory()->count(3)->create();

        $this->actingAs($staff)->get('/admin/users')->assertSuccessful();
    }

    public function test_staff_without_create_permission_cannot_access_creation_page(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $staff = $this->createUserWithRole(Role::Staff->value);

        $this->actingAs($staff)->get('/admin/users/create')->assertForbidden();
    }

    public function test_user_with_view_permission_can_view_a_user(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $staff = $this->createUserWithRole(Role::Staff->value);
        $target = User::factory()->create();

        $this->assertTrue($staff->can('view', $target));
    }

    public function test_user_without_delete_permission_cannot_delete_another_user(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $staff = $this->createUserWithRole(Role::Staff->value);
        $target = User::factory()->create();

        $this->assertFalse($staff->can('delete', $target));

        $this->actingAs($staff);

        $this->assertFalse(UserResource::canDelete($target));
    }

    public function test_admin_with_delete_permission_can_delete_another_user(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = $this->createUserWithRole(Role::Admin->value);
        $target = User::factory()->create();

        $this->assertTrue($admin->can('delete', $target));
    }

    public function test_a_user_cannot_delete_their_own_account(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = $this->createUserWithRole(Role::Admin->value);

        $this->assertFalse($admin->can('delete', $admin));
    }

    public function test_admin_cannot_edit_a_super_admin_user(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = $this->createUserWithRole(Role::Admin->value);
        $superAdmin = $this->createUserWithRole(Role::SuperAdmin->value);

        $this->assertFalse($admin->can('update', $superAdmin));
        $this->actingAs($admin)->get("/admin/users/{$superAdmin->id}/edit")->assertForbidden();
    }

    public function test_passwords_are_stored_hashed(): void
    {
        $user = User::factory()->create(['password' => 'a-secret-password']);

        $rawPassword = $user->getRawOriginal('password');

        $this->assertNotSame('a-secret-password', $rawPassword);
        $this->assertTrue(Hash::check('a-secret-password', $rawPassword));
    }
}
