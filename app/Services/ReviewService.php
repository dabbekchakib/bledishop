<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Enums\OrderStatus;
use App\Enums\ReviewStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewService
{
    /**
     * Whether the reviews feature is enabled (see Boutique config tab).
     */
    public function enabled(): bool
    {
        return (bool) setting('shop.reviews_enabled', false);
    }

    /**
     * Whether the given user is allowed to post a new review for this product.
     * A registered, active user may review. Guests may review only when the
     * shop allows guest reviews (defaults to true), but are still rate limited.
     */
    public function canReview(?User $user): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        if ($user !== null) {
            return $user->is_active;
        }

        return (bool) setting('reviews.allow_guests', true);
    }

    /**
     * True when the user already authored a review for this product.
     */
    public function hasReviewed(User $user, Product $product): bool
    {
        return ProductReview::query()
            ->where('product_id', $product->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Determine whether the user has a non-cancelled order containing the
     * product, i.e. a verified, real purchase.
     */
    public function hasVerifiedPurchase(User $user, Product $product): bool
    {
        return Order::query()
            ->where('user_id', $user->id)
            ->where('status', '!=', OrderStatus::Cancelled->value)
            ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
            ->exists();
    }

    /**
     * Submit a review. Always persists through a transaction, validates the
     * rating range and guards against duplicates for registered users.
     *
     * @throws ValidationException
     */
    public function submit(int $productId, int $rating, ?string $title, ?string $comment, ?User $user): ProductReview
    {
        if (! $this->enabled()) {
            throw ValidationException::withMessages(['rating' => __('shop.reviews.disabled')]);
        }

        if ($rating < 1 || $rating > 5) {
            throw ValidationException::withMessages(['rating' => __('shop.reviews.rating_invalid')]);
        }

        $product = Product::query()->findOrFail($productId);

        $verified = $user !== null && $this->hasVerifiedPurchase($user, $product);

        return DB::transaction(function () use ($product, $rating, $title, $comment, $user, $verified): ProductReview {
            if ($user !== null && $this->hasReviewed($user, $product)) {
                throw ValidationException::withMessages(['rating' => __('shop.reviews.already')]);
            }

            $review = ProductReview::create([
                'product_id' => $product->id,
                'user_id' => $user?->id,
                'rating' => $rating,
                'title' => filled($title) ? trim($title) : null,
                'comment' => filled($comment) ? trim($comment) : null,
                'status' => ReviewStatus::Pending,
                'verified_purchase' => $verified,
            ]);

            app(AdminNotificationService::class)->notify(
                NotificationType::ReviewCreated,
                $product,
            );

            app(AdminNotificationService::class)->notify(
                NotificationType::ReviewPendingModeration,
                $product,
            );

            return $review;
        });
    }

    /**
     * Update the moderation status (approve / reject). Setting approved also
     * records the approval timestamp. The product review cache is invalidated.
     */
    public function moderate(ProductReview $review, ReviewStatus $status): void
    {
        $review->forceFill([
            'status' => $status,
            'approved_at' => $status === ReviewStatus::Approved ? now() : $review->approved_at,
        ])->save();

        $this->forgetStats($review->product_id);
    }

    /**
     * Destroy a review and refresh the affected product cache.
     */
    public function delete(ProductReview $review): void
    {
        $productId = $review->product_id;

        $review->delete();

        $this->forgetStats($productId);
    }

    /**
     * Publicly visible (approved) reviews for a product, most recent first,
     * with the author loaded to avoid N+1.
     */
    public function approvedFor(int $productId): \Illuminate\Database\Eloquent\Collection
    {
        return ProductReview::query()
            ->where('product_id', $productId)
            ->approved()
            ->with('user')
            ->latest('id')
            ->get();
    }

    /**
     * Aggregate rating statistics for a product from its approved reviews.
     *
     * @return array{count: int, average: float, distribution: array<int, int>,
     *               rating_1: int, rating_2: int, rating_3: int, rating_4: int, rating_5: int}
     */
    public function stats(int $productId): array
    {
        $rows = ProductReview::query()
            ->where('product_id', $productId)
            ->approved()
            ->select('rating', DB::raw('count(*) as total'))
            ->groupBy('rating')
            ->get()
            ->keyBy('rating');

        $count = (int) $rows->sum('total');

        $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

        foreach ($rows as $rating => $row) {
            $distribution[(int) $rating] = (int) $row->total;
        }

        $weighted = collect($distribution)->reduce(
            fn (int $carry, int $count, int $rating): int => $carry + ($rating * $count),
            0,
        );

        $average = $count > 0 ? round($weighted / $count, 1) : 0.0;

        return [
            'count' => $count,
            'average' => $average,
            'distribution' => $distribution,
            'rating_1' => $distribution[1],
            'rating_2' => $distribution[2],
            'rating_3' => $distribution[3],
            'rating_4' => $distribution[4],
            'rating_5' => $distribution[5],
        ];
    }

    /**
     * Invalidate the (possibly cached) stats key for a product.
     */
    public function forgetStats(int $productId): void
    {
        if (method_exists(\Illuminate\Support\Facades\Cache::class, 'forget')) {
            \Illuminate\Support\Facades\Cache::forget('product_review_stats.'.$productId);
        }
    }
}
