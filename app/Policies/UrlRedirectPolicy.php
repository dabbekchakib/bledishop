<?php

namespace App\Policies;

use App\Models\UrlRedirect;
use App\Models\User;

class UrlRedirectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('redirects.view');
    }

    public function view(User $user, UrlRedirect $redirect): bool
    {
        return $user->can('redirects.view');
    }

    public function create(User $user): bool
    {
        return $user->can('redirects.create');
    }

    public function update(User $user, UrlRedirect $redirect): bool
    {
        return $user->can('redirects.update');
    }

    public function delete(User $user, UrlRedirect $redirect): bool
    {
        return $user->can('redirects.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('redirects.delete');
    }
}
