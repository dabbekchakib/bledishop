<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;
    use HasTranslations;
    use SoftDeletes;

    protected $fillable = [
        'brand_id',
        'type',
        'status',
        'featured',
        'sku',
        'price',
        'compare_at_price',
        'cost_price',
        'manage_stock',
        'stock_quantity',
        'low_stock_threshold',
        'stock_status',
        'weight',
        'length',
        'width',
        'height',
        'published_at',
    ];

    protected $casts = [
        'type' => ProductType::class,
        'status' => ProductStatus::class,
        'featured' => 'boolean',
        'price' => 'decimal:4',
        'compare_at_price' => 'decimal:4',
        'cost_price' => 'decimal:4',
        'manage_stock' => 'boolean',
        'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
        'weight' => 'decimal:2',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'published_at' => 'datetime',
    ];

    public static function translationModel(): string
    {
        return ProductTranslation::class;
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ProductTranslation::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_category');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function primaryImage(): HasMany
    {
        return $this->hasMany(ProductImage::class)->where('is_primary', true)->orderBy('id')->limit(1);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'attribute_product');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function isVariable(): bool
    {
        return $this->type === ProductType::Variable;
    }

    public function isSimple(): bool
    {
        return $this->type === ProductType::Simple;
    }

    /**
     * Total real stock across all active variants (variable products) or the
     * own quantity (simple products).
     */
    public function realStockQuantity(): int
    {
        if ($this->isVariable()) {
            return (int) $this->variants()->withTrashed()->sum('stock_quantity');
        }

        return (int) $this->stock_quantity;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Active->value);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Active->value);
    }

    public function scopeFeatured(Builder $query, bool $featured = true): Builder
    {
        return $query->where('featured', $featured);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeSimple(Builder $query): Builder
    {
        return $query->where('type', ProductType::Simple->value);
    }

    public function scopeVariable(Builder $query): Builder
    {
        return $query->where('type', ProductType::Variable->value);
    }
}
