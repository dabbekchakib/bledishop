<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\PromotionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'value',
        'is_flash_sale',
        'is_countdown',
        'countdown_title',
        'active',
        'starts_at',
        'ends_at',
        'product_ids',
        'category_ids',
        'brand_ids',
        'promo_quantity',
        'promo_quantity_used',
    ];

    protected $casts = [
        'type' => DiscountType::class,
        'value' => 'float',
        'is_flash_sale' => 'boolean',
        'is_countdown' => 'boolean',
        'active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'product_ids' => 'array',
        'category_ids' => 'array',
        'brand_ids' => 'array',
        'promo_quantity' => 'integer',
        'promo_quantity_used' => 'integer',
    ];

    public function status(?Carbon $now = null): PromotionStatus
    {
        return PromotionStatus::fromDates($this->starts_at, $this->ends_at, $now);
    }

    /**
     * Whether this promotion is currently running (dates + active flag).
     */
    public function isRunning(): bool
    {
        return $this->active && $this->status() === PromotionStatus::Active;
    }

    /**
     * Whether the promotional quantity budget has been exhausted.
     */
    public function budgetExhausted(): bool
    {
        return $this->promo_quantity !== null
            && $this->promo_quantity_used >= $this->promo_quantity;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeRunning(Builder $query): Builder
    {
        return $query
            ->where('active', true)
            ->where(function (Builder $q): void {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $q): void {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            });
    }
}
