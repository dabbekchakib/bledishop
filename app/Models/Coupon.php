<?php

namespace App\Models;

use App\Enums\DiscountType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'value',
        'min_subtotal',
        'max_subtotal',
        'usage_limit',
        'per_customer_limit',
        'product_ids',
        'category_ids',
        'brand_ids',
        'excluded_product_ids',
        'excluded_category_ids',
        'cumulative',
        'active',
        'starts_at',
        'ends_at',
        'usage_count',
    ];

    protected $casts = [
        'type' => DiscountType::class,
        'value' => 'float',
        'min_subtotal' => 'float',
        'max_subtotal' => 'float',
        'usage_limit' => 'integer',
        'per_customer_limit' => 'integer',
        'product_ids' => 'array',
        'category_ids' => 'array',
        'brand_ids' => 'array',
        'excluded_product_ids' => 'array',
        'excluded_category_ids' => 'array',
        'cumulative' => 'boolean',
        'active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'usage_count' => 'integer',
    ];

    public function getCastCode(): string
    {
        return strtoupper(trim($this->code));
    }

    /**
     * Basic lifecycle / usage rules. Restrictions (subtotal, products,
     * exclusion, per-customer) are evaluated by the shopping context.
     */
    public function isUsable(): bool
    {
        if (! $this->active) {
            return false;
        }

        if ($this->starts_at !== null && now()->lessThan($this->starts_at)) {
            return false;
        }

        if ($this->ends_at !== null && now()->greaterThan($this->ends_at)) {
            return false;
        }

        if ($this->usage_limit !== null && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Whether the coupon targets at least one present product / category /
     * brand. An empty target set (no restrictions) applies to the whole cart.
     *
     * @param  array<int, int>  $productIds
     * @param  array<int, int>  $categoryIds
     * @param  array<int, int>  $brandIds
     */
    public function appliesTo(array $productIds, array $categoryIds, array $brandIds): bool
    {
        $products = $this->product_ids ?: [];
        $categories = $this->category_ids ?: [];
        $brands = $this->brand_ids ?: [];

        if ($products === [] && $categories === [] && $brands === []) {
            return true;
        }

        return collect($products)->intersect($productIds)->isNotEmpty()
            || collect($categories)->intersect($categoryIds)->isNotEmpty()
            || collect($brands)->intersect($brandIds)->isNotEmpty();
    }

    /**
     * Whether the product is explicitly excluded.
     *
     * @param  array<int, int>  $categoryIds
     */
    public function isExcluded(int $productId, array $categoryIds): bool
    {
        if (in_array($productId, $this->excluded_product_ids ?: [], true)) {
            return true;
        }

        return collect($categoryIds)->intersect($this->excluded_category_ids ?: [])->isNotEmpty();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query
            ->where('active', true)
            ->where(function (Builder $q): void {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $q): void {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->where(function (Builder $q): void {
                $q->whereNull('usage_limit')->orWhereColumn('usage_count', '<', 'usage_limit');
            });
    }
}
