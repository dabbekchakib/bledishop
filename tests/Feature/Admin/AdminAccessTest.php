<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\InteractsWithRoles;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_admin(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect();
        $this->assertStringContainsString('/admin/login', $response->headers->get('Location'));
    }

    public function test_super_admin_can_access_the_admin_panel(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = $this->createUserWithRole(Role::SuperAdmin->value);

        $this->actingAs($user)->get('/admin')->assertSuccessful();
    }

    public function test_admin_can_access_the_admin_panel(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = $this->createUserWithRole(Role::Admin->value);

        $this->actingAs($user)->get('/admin')->assertSuccessful();
    }

    public function test_manager_can_access_the_admin_panel(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = $this->createUserWithRole(Role::Manager->value);

        $this->actingAs($user)->get('/admin')->assertSuccessful();
    }

    public function test_staff_can_access_the_admin_panel(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = $this->createUserWithRole(Role::Staff->value);

        $this->actingAs($user)->get('/admin')->assertSuccessful();
    }

    public function test_customer_cannot_access_the_admin_panel(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = $this->createUserWithRole(Role::Customer->value);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_user_without_role_cannot_access_the_admin_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_inactive_user_cannot_access_the_admin_panel(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = $this->createUserWithRole(Role::SuperAdmin->value);
        $user->update(['is_active' => false]);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_inactive_user_is_redirected_from_the_frontend_dashboard(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
