<?php

namespace App\Services;

use App\Enums\DiscountType;
use App\Models\Coupon;
use App\Models\DiscountRule;
use App\Support\DiscountResult;
use Illuminate\Support\Collection;

class DiscountService
{
    public function __construct(
        private readonly CouponService $coupons,
    ) {
    }

    /**
     * Compute the server-side discounts (applied coupon + automatic rules)
     * for a normalized cart array.
     *
     * Promotional prices are NOT handled here: they already flow through
     * PromotionService into each line's unit price (see CartService).
     *
     * @param  array<string, mixed>  $cart
     */
    public function calculateForCart(array $cart, ?int $userId = null): DiscountResult
    {
        $result = new DiscountResult();

        $subtotal = (float) ($cart['subtotal'] ?? 0);

        if ($subtotal <= 0) {
            return $result;
        }

        // 1. Automatic discount rules
        $this->applyRules($cart, $result);

        // 2. Applied coupon
        $this->applyCoupon($cart, $result, $userId);

        // Free-shipping overrides the normal shipping cost when shipping is
        // enabled and the result marks it so.
        if ($result->freeShipping) {
            $result->add([
                'kind' => 'shipping',
                'discountable_type' => null,
                'discountable_id' => null,
                'code' => null,
                'name' => __('admin.marketing.free_shipping'),
                'type' => DiscountType::FreeShipping->value,
                'value' => 0,
                'amount' => (int) ($cart['totals']['shipping'] ?? 0),
            ]);
        }

        return $result;
    }

    private function applyRules(array $cart, DiscountResult $result): void
    {
        if (! (bool) setting('marketing.rules_enabled', true)) {
            return;
        }

        $scopes = $this->coupons->cartScopes($cart);
        $subtotal = (float) ($cart['subtotal'] ?? 0);

        $rules = $this->runningRules()
            ->filter(fn (DiscountRule $rule) => $this->ruleApplies($rule, $scopes, $subtotal, $cart));

        // Non-cumulative engine: apply only the single best rule (highest
        // priority, then largest discount).
        $eligible = $rules
            ->sortByDesc('priority')
            ->first(fn (DiscountRule $rule) => $rule->value > 0);

        if (! $eligible) {
            return;
        }

        $amount = match ($eligible->type) {
            DiscountType::Percentage => round($subtotal * $eligible->value / 100, 2),
            DiscountType::Fixed, DiscountType::PromoPrice => min($eligible->value, $subtotal),
            DiscountType::FreeShipping => 0.0,
        };

        if ($amount <= 0) {
            return;
        }

        $result->add([
            'kind' => 'rule',
            'discountable_type' => DiscountRule::class,
            'discountable_id' => (int) $eligible->getKey(),
            'code' => null,
            'name' => $eligible->name,
            'type' => $eligible->type->value,
            'value' => (float) $eligible->value,
            'amount' => $amount,
        ]);

        if ($eligible->type === DiscountType::FreeShipping && (bool) setting('shipping.enabled', true)) {
            $result->freeShipping = true;
        }
    }

    private function applyCoupon(array $cart, DiscountResult $result, ?int $userId): void
    {
        $code = $cart['coupon_code'] ?? null;

        if (! $code || ! (bool) setting('marketing.coupons_enabled', true)) {
            return;
        }

        $coupon = $this->coupons->findByCode($code);

        if (! $coupon) {
            $result->errors[] = 'marketing.coupon.invalid';

            return;
        }

        $validation = $this->coupons->validateForCart($coupon, $cart, $userId);

        if (! $validation['ok']) {
            $result->errors[] = $validation['error'];

            return;
        }

        $amount = $this->coupons->amountFor($coupon, (float) ($cart['subtotal'] ?? 0));

        if ($coupon->type === DiscountType::FreeShipping && (bool) setting('shipping.enabled', true)) {
            $result->freeShipping = true;
        }

        if ($amount > 0) {
            $result->add([
                'kind' => 'coupon',
                'discountable_type' => Coupon::class,
                'discountable_id' => (int) $coupon->getKey(),
                'code' => $coupon->getCastCode(),
                'name' => $coupon->name ?: $coupon->getCastCode(),
                'type' => $coupon->type->value,
                'value' => (float) $coupon->value,
                'amount' => $amount,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $scopes
     * @param  array<string, mixed>  $cart
     */
    private function ruleApplies(DiscountRule $rule, array $scopes, float $subtotal, array $cart): bool
    {
        if (! $rule->isUsable()) {
            return false;
        }

        if ($rule->min_subtotal !== null && $subtotal < (float) $rule->min_subtotal) {
            return false;
        }

        if ($rule->min_items !== null && (int) $scopes['count'] < (int) $rule->min_items) {
            return false;
        }

        if ($rule->min_quantity !== null) {
            $quantity = array_sum(array_map(
                fn ($line) => (int) ($line['quantity'] ?? 0),
                array_filter($cart['items'] ?? [], 'is_array')
            ));

            if ($quantity < (int) $rule->min_quantity) {
                return false;
            }
        }

        $products = $rule->product_ids ?: [];
        $categories = $rule->category_ids ?: [];
        $brands = $rule->brand_ids ?: [];

        if ($products === [] && $categories === [] && $brands === []) {
            return true;
        }

        return collect($products)->intersect($scopes['product_ids'])->isNotEmpty()
            || collect($categories)->intersect($scopes['category_ids'])->isNotEmpty()
            || collect($brands)->intersect($scopes['brand_ids'])->isNotEmpty();
    }

    /**
     * @return Collection<int, DiscountRule>
     */
    private function runningRules(): Collection
    {
        return DiscountRule::query()
            ->where('active', true)
            ->where(function ($q): void {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q): void {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->get();
    }
}
