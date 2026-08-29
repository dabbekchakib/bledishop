<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\Feature\Concerns\InteractsWithRoles;
use Tests\TestCase;

class SuperAdminTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    public function test_seeder_creates_a_super_admin_account(): void
    {
        $this->seed();

        $user = User::where('email', config('superadmin.email'))->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole(Role::SuperAdmin->value));
        $this->assertTrue($user->isSuperAdmin());
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check(config('superadmin.password'), $user->password));
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(1, User::where('email', config('superadmin.email'))->count());
        $this->assertSame(5, SpatieRole::count());
        $this->assertSame(48, Permission::count());
        $this->assertSame(1, User::role(Role::SuperAdmin->value)->count());
    }

    public function test_super_admin_has_access_to_all_administrative_permissions(): void
    {
        $this->seed();

        $user = User::where('email', config('superadmin.email'))->first();

        foreach (['users.view', 'users.create', 'users.update', 'users.delete', 'roles.view', 'roles.update', 'permissions.delete', 'orders.view', 'orders.update', 'orders.change_status', 'orders.export', 'orders.print', 'customers.view', 'customers.update', 'customers.activate', 'customers.export'] as $permission) {
            $this->assertTrue($user->can($permission), "Missing permission: {$permission}");
        }
    }

    public function test_super_admin_can_access_administrative_resources(): void
    {
        $this->seed();

        $user = User::where('email', config('superadmin.email'))->first();

        $this->actingAs($user)->get('/admin')->assertSuccessful();
        $this->actingAs($user)->get('/admin/users')->assertSuccessful();
        $this->actingAs($user)->get('/admin/roles')->assertSuccessful();
        $this->actingAs($user)->get('/admin/permissions')->assertSuccessful();
        $this->actingAs($user)->get('/admin/categories')->assertSuccessful();
        $this->actingAs($user)->get('/admin/brands')->assertSuccessful();
        $this->actingAs($user)->get('/admin/orders')->assertSuccessful();
        $this->actingAs($user)->get('/admin/customers')->assertSuccessful();
    }
}
