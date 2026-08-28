<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function view(User $user): bool
    {
        return $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $target): bool
    {
        if (! $user->can('users.update')) {
            return false;
        }

        if ($target->isSuperAdmin() && ! $user->isSuperAdmin()) {
            return false;
        }

        return true;
    }

    public function delete(User $user, User $target): bool
    {
        if (! $user->can('users.delete')) {
            return false;
        }

        if ($user->is($target)) {
            return false;
        }

        if ($target->isSuperAdmin() && ! $user->isSuperAdmin()) {
            return false;
        }

        return true;
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('users.delete');
    }
}
