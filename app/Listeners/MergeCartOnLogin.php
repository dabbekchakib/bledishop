<?php

namespace App\Listeners;

use App\Services\CartService;
use Illuminate\Auth\Events\Login;

/**
 * Merge the guest session cart into the user's persistent cart right after
 * authentication. The guest session items are still present at this point, so
 * they can be imported before the session cart is discarded.
 */
class MergeCartOnLogin
{
    public function handle(Login $event): void
    {
        try {
            app(CartService::class)->merge();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
