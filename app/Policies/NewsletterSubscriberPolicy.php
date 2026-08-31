<?php

namespace App\Policies;

use App\Models\NewsletterSubscriber;
use App\Models\User;

class NewsletterSubscriberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('newsletter.view');
    }

    public function view(User $user, NewsletterSubscriber $subscriber): bool
    {
        return $user->can('newsletter.view');
    }

    public function create(User $user): bool
    {
        return $user->can('newsletter.manage');
    }

    public function update(User $user, NewsletterSubscriber $subscriber): bool
    {
        return $user->can('newsletter.manage');
    }

    public function delete(User $user, NewsletterSubscriber $subscriber): bool
    {
        return $user->can('newsletter.manage');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('newsletter.manage');
    }
}
