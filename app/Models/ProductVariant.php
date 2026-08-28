<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'sku',
        'price',
        'compare_at_price',
        'cost_price',
        'manage_stock',
        'stock_quantity',
        'low_stock_threshold',
        'stock_status',
        'weight',
        'image',
    ];

    protected $casts = [
        'price' => 'decimal:4',
        'compare_at_price' => 'decimal:4',
        'cost_price' => 'decimal:4',
        'manage_stock' => 'boolean',
        'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
        'weight' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_variant_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'product_variant_id');
    }

    /**
     * The attribute/value pairs describing this variant (e.g. Taille → M).
     */
    public function variantValues(): HasMany
    {
        return $this->hasMany(ProductVariantValue::class, 'variant_id');
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductVariantValue::class, 'variant_id');
    }

    /**
     * Readable combination label for the current/default locale,
     * e.g. "Rouge / M".
     */
    public function combinationLabel(): string
    {
        $parts = $this->variantValues()
            ->with(['attributeValue.translations'])
            ->get()
            ->sortBy(fn (ProductVariantValue $value): int => (int) $value->attribute?->sort_order)
            ->map(function (ProductVariantValue $value): string {
                $translation = $value->attributeValue?->translation();

                return $translation?->label ?? $value->attributeValue?->value ?? '';
            })
            ->filter()
            ->all();

        return implode(' / ', $parts);
    }
}
