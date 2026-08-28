<?php

namespace App\Policies;

use App\Enums\Role as UserRole;
use App\Models\User;
use Spatie\Permission\Models\Role as SpatieRole;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('roles.view');
    }

    public function view(User $user): bool
    {
        return $user->can('roles.view');
    }

    public function create(User $user): bool
    {
        return $user->can('roles.create');
    }

    public function update(User $user, SpatieRole $role): bool
    {
        if (! $user->can('roles.update')) {
            return false;
        }

        return $role->name !== UserRole::SuperAdmin->value;
    }

    public function delete(User $user, SpatieRole $role): bool
    {
        if (! $user->can('roles.delete')) {
            return false;
        }

        return $role->name !== UserRole::SuperAdmin->value;
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('roles.delete');
    }
}
