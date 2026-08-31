<?php

namespace App\Policies;

use App\Models\ProductReview;
use App\Models\User;

class ProductReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reviews.view');
    }

    public function view(User $user, ProductReview $review): bool
    {
        return $user->can('reviews.view');
    }

    public function create(User $user): bool
    {
        return $user->can('reviews.manage');
    }

    public function update(User $user, ProductReview $review): bool
    {
        return $user->can('reviews.manage');
    }

    /**
     * Approve / reject actions are restricted to moderators.
     */
    public function moderate(User $user, ProductReview $review): bool
    {
        return $user->can('reviews.moderate');
    }

    public function delete(User $user, ProductReview $review): bool
    {
        return $user->can('reviews.manage');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('reviews.manage');
    }
}
