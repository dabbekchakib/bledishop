<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * A customer can only view orders assigned to their own account.
     */
    public function view(?User $user, Order $order): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->getKey() === $order->user_id;
    }
}
