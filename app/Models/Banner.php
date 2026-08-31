<?php

namespace App\Models;

use App\Enums\BannerPosition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'image',
        'description',
        'link',
        'button_label',
        'position',
        'sort_order',
        'active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'position' => BannerPosition::class,
        'sort_order' => 'integer',
        'active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function isVisible(): bool
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

    public function scopeVisible(Builder $query, ?BannerPosition $position = null): Builder
    {
        $query->where('active', true)
            ->where(function (Builder $q): void {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $q): void {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            });

        if ($position !== null) {
            $query->where('position', $position->value);
        }

        return $query->orderBy('sort_order');
    }
}
