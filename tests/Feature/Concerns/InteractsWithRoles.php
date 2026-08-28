<?php

namespace Tests\Feature\Concerns;

use App\Models\User;
use Spatie\Permission\Models\Role as SpatieRole;

trait InteractsWithRoles
{
    /**
     * @param  array<int, string>  $permissions
     */
    protected function createRole(string $name, array $permissions = []): SpatieRole
    {
        $role = SpatieRole::firstOrCreate([
            'name' => $name,
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($permissions);

        return $role;
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function createUserWithRole(string $roleName, array $permissions = []): User
    {
        $user = User::factory()->create();

        $user->syncRoles([$roleName]);

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }
}
