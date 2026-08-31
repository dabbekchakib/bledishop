<?php

namespace App\Models;

use App\Enums\DiscountType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'value',
        'priority',
        'cumulative',
        'active',
        'min_subtotal',
        'min_quantity',
        'min_items',
        'product_ids',
        'category_ids',
        'brand_ids',
        'customer_ids',
        'first_purchase_only',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'type' => DiscountType::class,
        'value' => 'float',
        'priority' => 'integer',
        'cumulative' => 'boolean',
        'active' => 'boolean',
        'min_subtotal' => 'float',
        'min_quantity' => 'integer',
        'min_items' => 'integer',
        'product_ids' => 'array',
        'category_ids' => 'array',
        'brand_ids' => 'array',
        'customer_ids' => 'array',
        'first_purchase_only' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

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

        return true;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
