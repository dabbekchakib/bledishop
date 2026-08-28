<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductImage extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'path',
        'alt',
        'sort_order',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    public static function translationModel(): string
    {
        return ProductImageTranslation::class;
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ProductImageTranslation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function translatedAlt(?string $locale = null): string
    {
        return (string) ($this->translation($locale)?->alt ?? $this->alt ?? '');
    }
}
