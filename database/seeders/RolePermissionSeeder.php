<?php

namespace Database\Seeders;

use App\Enums\Role as UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed roles and permissions. Idempotent.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = $this->permissions();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $rolePermissions = [
            UserRole::SuperAdmin->value => $permissions,
            UserRole::Admin->value => $permissions,
            UserRole::Manager->value => [
                'users.view',
                'users.update',
                'roles.view',
                'permissions.view',
                'settings.view',
                'categories.view',
                'categories.update',
                'brands.view',
                'brands.update',
                'products.view',
                'products.create',
                'products.update',
                'attributes.view',
                'attributes.create',
                'attributes.update',
                'stock.view',
                'stock.edit',
                'orders.view',
                'orders.update',
                'orders.change_status',
                'orders.export',
                'orders.print',
            ],
            UserRole::Staff->value => [
                'users.view',
                'orders.view',
            ],
            UserRole::Customer->value => [],
        ];

        foreach ($rolePermissions as $roleName => $permissionNames) {
            $role = SpatieRole::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($permissionNames);
        }
    }

    /**
     * @return array<int, string>
     */
    private function permissions(): array
    {
        $permissions = [];

        foreach (['users', 'roles', 'permissions', 'settings', 'categories', 'brands'] as $entity) {
            foreach (['view', 'create', 'update', 'delete'] as $action) {
                $permissions[] = "{$entity}.{$action}";
            }
        }

        foreach (['view', 'create', 'update', 'delete', 'publish'] as $action) {
            $permissions[] = "products.{$action}";
        }

        foreach (['view', 'create', 'update', 'delete'] as $action) {
            $permissions[] = "attributes.{$action}";
        }

        $permissions[] = 'stock.view';
        $permissions[] = 'stock.edit';

        foreach (['view', 'create', 'update', 'delete', 'change_status', 'export', 'print'] as $action) {
            $permissions[] = "orders.{$action}";
        }

        return $permissions;
    }
}
