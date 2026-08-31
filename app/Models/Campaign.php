<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'active',
        'starts_at',
        'ends_at',
        'promotion_ids',
        'coupon_ids',
        'banner_ids',
    ];

    protected $casts = [
        'active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'promotion_ids' => 'array',
        'coupon_ids' => 'array',
        'banner_ids' => 'array',
    ];

    public function promotions()
    {
        return Promotion::query()->whereIn('id', $this->promotion_ids ?: [])->get();
    }

    public function coupons()
    {
        return Coupon::query()->whereIn('id', $this->coupon_ids ?: [])->get();
    }

    public function banners()
    {
        return Banner::query()->whereIn('id', $this->banner_ids ?: [])->get();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
