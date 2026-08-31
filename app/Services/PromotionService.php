<?php

namespace App\Services;

use App\Enums\DiscountType;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Promotion;
use Illuminate\Support\Collection;

class PromotionService
{
    private static ?Collection $running = null;

    /**
     * All currently running promotions, cached for the request to avoid
     * repeated queries when pricing many products in a loop.
     *
     * @return Collection<int, Promotion>
     */
    public function runningPromotions(): Collection
    {
        if (static::$running === null) {
            static::$running = Promotion::query()
                ->where('active', true)
                ->where(function ($q): void {
                    $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                })
                ->where(function ($q): void {
                    $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
                })
                ->get();
        }

        return static::$running;
    }

    public function forgetCache(): void
    {
        static::$running = null;
    }

    /**
     * The promotional base unit price (HT, shop currency) for a product/variant
     * when an active promotion applies, otherwise null.
     */
    public function promoPriceFor(Product $product, ?ProductVariant $variant = null): ?float
    {
        return $this->candidate($product, $variant)['price'] ?? null;
    }

    /**
     * The best active promotion matching the product, when any, so the storefront
     * can render a countdown / badge for it.
     */
    public function promoPromotionFor(Product $product, ?ProductVariant $variant = null): ?Promotion
    {
        return $this->candidate($product, $variant)['promotion'] ?? null;
    }

    /**
     * Computes the cheapest promo price and the promotion behind it for a
     * product/variant, honouring promo budgets.
     *
     * @return array{price: ?float, promotion: ?Promotion}
     */
    private function candidate(Product $product, ?ProductVariant $variant = null): array
    {
        if (! (bool) setting('marketing.enabled', false)) {
            return ['price' => null, 'promotion' => null];
        }

        $base = $this->basePrice($product, $variant);

        if ($base === null) {
            return ['price' => null, 'promotion' => null];
        }

        $categoryIds = $this->categoryIds($product);

        $best = null;
        $bestPromotion = null;

        foreach ($this->runningPromotions() as $promotion) {
            if ($promotion->budgetExhausted()) {
                continue;
            }

            if (! $this->matches($promotion, $product, $categoryIds)) {
                continue;
            }

            $promo = $this->apply($promotion, $base);

            if ($promo !== null && ($best === null || $promo < $best)) {
                $best = $promo;
                $bestPromotion = $promotion;
            }
        }

        return ['price' => $best, 'promotion' => $bestPromotion];
    }

    /**
     * The original (non-promoted) base unit price, keeping resolvePrice aware
     * of the contrast between the raw price and the promoted one.
     */
    public function originalPrice(Product $product, ?ProductVariant $variant = null): ?float
    {
        return $this->basePrice($product, $variant);
    }

    private function apply(Promotion $promotion, float $base): ?float
    {
        return match ($promotion->type) {
            DiscountType::Percentage => round(max(0, $base * (1 - $promotion->value / 100)), 4),
            DiscountType::Fixed => round(max(0, $base - $promotion->value), 4),
            DiscountType::PromoPrice => round($promotion->value, 4),
            DiscountType::FreeShipping => null,
        };
    }

    private function basePrice(Product $product, ?ProductVariant $variant): ?float
    {
        if ($product->isVariable()) {
            return $variant ? (float) $variant->price : (float) $product->displayPrice();
        }

        return (float) $product->displayPrice();
    }

    /**
     * @return array<int, int>
     */
    private function categoryIds(Product $product): array
    {
        if ($product->relationLoaded('categories')) {
            return $product->categories->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        return $product->categories()->pluck('categories.id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @param  array<int, int>  $categoryIds
     */
    private function matches(Promotion $promotion, Product $product, array $categoryIds): bool
    {
        $products = $promotion->product_ids ?: [];
        $categories = $promotion->category_ids ?: [];
        $brands = $promotion->brand_ids ?: [];

        if ($products === [] && $categories === [] && $brands === []) {
            return true;
        }

        if (in_array((int) $product->getKey(), $products, true)) {
            return true;
        }

        if ($product->brand && in_array((int) $product->brand_id, $brands, true)) {
            return true;
        }

        return collect($categories)->intersect($categoryIds)->isNotEmpty();
    }
}
