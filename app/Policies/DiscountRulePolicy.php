<?php

namespace App\Policies;

use App\Models\DiscountRule;
use App\Models\User;

class DiscountRulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('discount_rules.view');
    }

    public function view(User $user, DiscountRule $rule): bool
    {
        return $user->can('discount_rules.view');
    }

    public function create(User $user): bool
    {
        return $user->can('discount_rules.create');
    }

    public function update(User $user, DiscountRule $rule): bool
    {
        return $user->can('discount_rules.update');
    }

    public function delete(User $user, DiscountRule $rule): bool
    {
        return $user->can('discount_rules.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('discount_rules.delete');
    }
}
