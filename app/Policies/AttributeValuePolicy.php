<?php

namespace App\Policies;

use App\Models\AttributeValue;
use App\Models\User;

class AttributeValuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('attributes.view');
    }

    public function view(User $user, AttributeValue $attributeValue): bool
    {
        return $user->can('attributes.view');
    }

    public function create(User $user): bool
    {
        return $user->can('attributes.create');
    }

    public function update(User $user, AttributeValue $attributeValue): bool
    {
        return $user->can('attributes.update');
    }

    public function delete(User $user, AttributeValue $attributeValue): bool
    {
        return $user->can('attributes.delete');
    }
}
