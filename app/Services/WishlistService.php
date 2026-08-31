<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class WishlistService
{
    /**
     * Whether the wishlist feature is enabled (see Boutique config tab).
     */
    public function enabled(): bool
    {
        return (bool) setting('shop.wishlist_enabled', false);
    }

    /**
     * The storage owner for the current visit: the authenticated user's id, or
     * the guest session id. Provides the same behaviour for both types.
     */
    protected function owner(?User $user, ?string $sessionId): array
    {
        if ($user !== null) {
            return ['user_id' => $user->id, 'session_id' => null];
        }

        return ['user_id' => null, 'session_id' => $sessionId];
    }

    /**
     * All product rows on the wishlist for the given owner.
     */
    public function list(?User $user, ?string $sessionId): Collection
    {
        $owner = $this->owner($user, $sessionId);

        $query = WishlistItem::query()->with('product');

        if ($owner['user_id'] !== null) {
            $query->where('user_id', $owner['user_id'])->whereNull('session_id');
        } elseif ($owner['session_id'] !== null) {
            $query->where('session_id', $owner['session_id'])->whereNull('user_id');
        } else {
            return new Collection();
        }

        return $query
            ->latest('id')
            ->get()
            ->map->product
            ->filter()
            ->values();
    }

    /**
     * The product ids on the wishlist for the owner (used by the frontend to
     * render heart states and to avoid N+1 look-ups).
     *
     * @return array<int, int>
     */
    public function ids(?User $user, ?string $sessionId): array
    {
        $owner = $this->owner($user, $sessionId);

        $query = WishlistItem::query()->where('product_id', '>', 0);

        if ($owner['user_id'] !== null) {
            $query->where('user_id', $owner['user_id'])->whereNull('session_id');
        } else {
            $query->where('session_id', $owner['session_id'])->whereNull('user_id');
        }

        return $query->pluck('product_id')->map(fn ($id): int => (int) $id)->all();
    }

    /**
     * Add a product to the wishlist. Idempotent and de-duplicated.
     *
     * @return array{success: bool, in_wishlist: bool, count: int}
     */
    public function add(Product $product, ?User $user, ?string $sessionId): array
    {
        $owner = $this->owner($user, $sessionId);

        $existing = WishlistItem::query()
            ->where('product_id', $product->id)
            ->where('user_id', $owner['user_id'])
            ->where('session_id', $owner['session_id'])
            ->first();

        if ($existing !== null) {
            return [
                'success' => true,
                'in_wishlist' => true,
                'count' => $this->count($user, $sessionId),
            ];
        }

        WishlistItem::create([
            'product_id' => $product->id,
            'user_id' => $owner['user_id'],
            'session_id' => $owner['session_id'],
        ]);

        return [
            'success' => true,
            'in_wishlist' => true,
            'count' => $this->count($user, $sessionId),
        ];
    }

    /**
     * Remove a product from the wishlist.
     *
     * @return array{success: bool, in_wishlist: bool, count: int}
     */
    public function remove(Product $product, ?User $user, ?string $sessionId): array
    {
        $owner = $this->owner($user, $sessionId);

        WishlistItem::query()
            ->where('product_id', $product->id)
            ->where('user_id', $owner['user_id'])
            ->where('session_id', $owner['session_id'])
            ->delete();

        return [
            'success' => true,
            'in_wishlist' => false,
            'count' => $this->count($user, $sessionId),
        ];
    }

    /**
     * Toggle membership and return the resulting state.
     *
     * @return array{success: bool, in_wishlist: bool, count: int}
     */
    public function toggle(Product $product, ?User $user, ?string $sessionId): array
    {
        $owner = $this->owner($user, $sessionId);

        $exists = WishlistItem::query()
            ->where('product_id', $product->id)
            ->where('user_id', $owner['user_id'])
            ->where('session_id', $owner['session_id'])
            ->exists();

        return $exists
            ? $this->remove($product, $user, $sessionId)
            : $this->add($product, $user, $sessionId);
    }

    /**
     * Number of wishlist items for the owner.
     */
    public function count(?User $user, ?string $sessionId): int
    {
        $owner = $this->owner($user, $sessionId);

        $query = WishlistItem::query();

        if ($owner['user_id'] !== null) {
            $query->where('user_id', $owner['user_id'])->whereNull('session_id');
        } elseif ($owner['session_id'] !== null) {
            $query->where('session_id', $owner['session_id'])->whereNull('user_id');
        } else {
            return 0;
        }

        return (int) $query->count();
    }

    /**
     * Merge a guest's session wishlist into the authenticated user's wishlist
     * when they log in, then clear the guest rows. De-duplicated.
     */
    public function mergeGuestToUser(string $sessionId, User $user): void
    {
        $guestItems = WishlistItem::query()
            ->where('session_id', $sessionId)
            ->whereNull('user_id')
            ->get();

        if ($guestItems->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($guestItems, $user, $sessionId): void {
            foreach ($guestItems as $item) {
                $exists = WishlistItem::query()
                    ->where('product_id', $item->product_id)
                    ->where('user_id', $user->id)
                    ->exists();

                if (! $exists) {
                    WishlistItem::create([
                        'product_id' => $item->product_id,
                        'user_id' => $user->id,
                        'session_id' => null,
                    ]);
                }

                $item->delete();
            }
        });
    }
}
