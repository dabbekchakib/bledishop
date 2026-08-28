<?php

namespace App\Policies;

use App\Models\User;

class PermissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('permissions.view');
    }

    public function view(User $user): bool
    {
        return $user->can('permissions.view');
    }

    public function create(User $user): bool
    {
        return $user->can('permissions.create');
    }

    public function update(User $user): bool
    {
        return $user->can('permissions.update');
    }

    public function delete(User $user): bool
    {
        return $user->can('permissions.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('permissions.delete');
    }
}
