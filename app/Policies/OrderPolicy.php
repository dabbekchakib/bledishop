<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Whether the user may list orders in the admin panel.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('orders.view');
    }

    /**
     * A customer can only view their own orders; an administrator with the
     * `orders.view` permission can view any order.
     */
    public function view(?User $user, Order $order): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->can('orders.view')) {
            return true;
        }

        return $user->getKey() === $order->user_id;
    }

    public function create(User $user): bool
    {
        return $user->can('orders.create');
    }

    public function update(User $user, Order $order): bool
    {
        return $user->can('orders.update');
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->can('orders.delete');
    }
}
