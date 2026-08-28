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
            ],
            UserRole::Staff->value => [
                'users.view',
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

        return $permissions;
    }
}
