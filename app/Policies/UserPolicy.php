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

    /**
     * Customer management operates on the same User model but is gated by the
     * dedicated customers.* permissions. A customer is a user holding the
     * Customer role.
     */
    public function viewCustomerAny(User $user): bool
    {
        return $user->can('customers.view');
    }

    public function viewCustomer(User $user): bool
    {
        return $user->can('customers.view');
    }

    public function updateCustomer(User $user): bool
    {
        return $user->can('customers.update');
    }

    public function deleteCustomer(User $user): bool
    {
        return $user->can('customers.delete');
    }

    public function activateCustomer(User $user): bool
    {
        return $user->can('customers.activate');
    }
}
