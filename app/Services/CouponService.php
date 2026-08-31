<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderDiscount;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CouponService
{
    public function findByCode(string $code): ?Coupon
    {
        return Coupon::query()->valid()->where('code', $code)->first();
    }

    /**
     * Extract the unique product/category/brand ids present in the cart lines.
     *
     * @return array<int, array<string, mixed>>
     */
    public function cartScopes(array $cart): array
    {
        $productIds = [];
        $categoryIds = [];
        $brandIds = [];
        $subtotal = (float) ($cart['subtotal'] ?? 0);
        $count = (int) ($cart['count'] ?? 0);

        foreach ($cart['items'] ?? [] as $line) {
            if (! is_array($line)) {
                continue;
            }

            $productId = (int) ($line['product_id'] ?? 0);
            if ($productId > 0) {
                $productIds[] = $productId;
            }

            $brandId = (int) ($line['brand_id'] ?? data_get($line, 'product.brand_id'));
            if ($brandId > 0) {
                $brandIds[] = $brandId;
            }
        }

        if ($productIds !== []) {
            $categoryIds = DB::table('product_category')->whereIn('product_id', $productIds)
                ->pluck('category_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        return [
            'product_ids' => array_values(array_unique($productIds)),
            'category_ids' => $categoryIds,
            'brand_ids' => array_values(array_unique($brandIds)),
            'subtotal' => $subtotal,
            'count' => $count,
        ];
    }

    /**
     * Validate a coupon against the current cart / customer context.
     *
     * @param  array<string, mixed>  $cart
     * @return array{ok: bool, error?: string}
     */
    public function validateForCart(Coupon $coupon, array $cart, ?int $userId): array
    {
        if (! $coupon->isUsable()) {
            return ['ok' => false, 'error' => 'marketing.coupon.unusable'];
        }

        if ($coupon->usage_count === null && $this->usageCount($coupon) >= ($coupon->usage_limit ?: PHP_INT_MAX)) {
            return ['ok' => false, 'error' => 'marketing.coupon.usage_limit'];
        }

        if ($userId && $coupon->per_customer_limit !== null) {
            $used = $this->userUsageCount($coupon, $userId);
            if ($used >= $coupon->per_customer_limit) {
                return ['ok' => false, 'error' => 'marketing.coupon.per_customer'];
            }
        }

        $scopes = $this->cartScopes($cart);
        $subtotal = (float) ($cart['subtotal'] ?? 0);

        if ($coupon->min_subtotal !== null && $subtotal < (float) $coupon->min_subtotal) {
            return ['ok' => false, 'error' => 'marketing.coupon.min_subtotal'];
        }

        if ($coupon->max_subtotal !== null && $subtotal >= (float) $coupon->max_subtotal) {
            return ['ok' => false, 'error' => 'marketing.coupon.max_subtotal'];
        }

        if (! $coupon->appliesTo($scopes['product_ids'], $scopes['category_ids'], $scopes['brand_ids'])) {
            return ['ok' => false, 'error' => 'marketing.coupon.not_applicable'];
        }

        foreach ($cart['items'] ?? [] as $line) {
            if (! is_array($line)) {
                continue;
            }

            $productId = (int) ($line['product_id'] ?? 0);
            $lineCategories = DB::table('product_category')->where('product_id', $productId)
                ->pluck('category_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($coupon->isExcluded($productId, $lineCategories)) {
                return ['ok' => false, 'error' => 'marketing.coupon.not_applicable'];
            }
        }

        return ['ok' => true];
    }

    private function usageCount(Coupon $coupon): int
    {
        return OrderDiscount::query()
            ->where('discountable_type', Coupon::class)
            ->where('discountable_id', $coupon->getKey())
            ->count();
    }

    private function userUsageCount(Coupon $coupon, ?int $userId): int
    {
        return OrderDiscount::query()
            ->where('discountable_type', Coupon::class)
            ->where('discountable_id', $coupon->getKey())
            ->whereIn('order_id', Order::query()->where('user_id', $userId)->select('id'))
            ->count();
    }

    /**
     * The discounted value (in gross units) a coupon yields on a subtotal.
     */
    public function amountFor(Coupon $coupon, float $subtotal): float
    {
        return match ($coupon->type) {
            \App\Enums\DiscountType::Percentage => round($subtotal * $coupon->value / 100, 2),
            \App\Enums\DiscountType::Fixed, \App\Enums\DiscountType::PromoPrice => min($coupon->value, $subtotal),
            \App\Enums\DiscountType::FreeShipping => 0.0,
        };
    }
}
