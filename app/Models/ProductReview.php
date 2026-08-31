<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductReview extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'user_id',
        'rating',
        'title',
        'comment',
        'status',
        'verified_purchase',
        'approved_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'status' => ReviewStatus::class,
        'verified_purchase' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', ReviewStatus::Approved->value);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ReviewStatus::Pending->value);
    }

    public function isApproved(): bool
    {
        return $this->status === ReviewStatus::Approved;
    }

    /**
     * Display author name: account name, or a neutral label when the author
     * deleted their account (user_id set to NULL by a null-on-delete FK).
     */
    public function authorName(): string
    {
        if ($this->user_id !== null && $this->relationLoaded('user') && $this->user !== null) {
            return $this->user->fullName();
        }

        return __('shop.reviews.anonymous');
    }

    /**
     * Convenience accessors used by admin lists and exports. The product name
     * uses the translatable fallback so the admin always sees a readable name.
     */
    protected function productName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->product?->translatedName() ?? '—',
        );
    }

    protected function productSku(): Attribute
    {
        return Attribute::make(
            get: fn (): string => (string) ($this->product?->sku ?? ''),
        );
    }

    protected function author(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->authorName(),
        );
    }
}
