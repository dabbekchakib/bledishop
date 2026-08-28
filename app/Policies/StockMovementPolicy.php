<?php

namespace App\Policies;

use App\Models\StockMovement;
use App\Models\User;

class StockMovementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('stock.view');
    }

    public function view(User $user, StockMovement $movement): bool
    {
        return $user->can('stock.view');
    }

    public function create(User $user): bool
    {
        return $user->can('stock.edit');
    }

    public function update(User $user, StockMovement $movement): bool
    {
        return $user->can('stock.edit');
    }

    public function delete(User $user, StockMovement $movement): bool
    {
        return $user->can('stock.edit');
    }
}
