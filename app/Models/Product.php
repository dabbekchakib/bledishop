<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\StockStatus;
use App\Models\Concerns\HasTranslations;
use App\Services\PromotionService;
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

    /**
     * Restrict the query to products currently out of stock: a simple product
     * with tracked stock at or below zero, or a variable product without any
     * available variant.
     */
    public function scopeOutOfStock(Builder $query): Builder
    {
        $backorder = StockStatus::OnBackorder->value;

        return $query->where(function (Builder $simple) use ($backorder): void {
            $simple->where('type', ProductType::Simple->value)
                ->where('manage_stock', true)
                ->where('stock_quantity', '<=', 0)
                ->where(fn (Builder $status): Builder => $status
                    ->where('stock_status', '!=', $backorder)
                    ->orWhereNull('stock_status'));
        })->orWhere(function (Builder $variable) use ($backorder): void {
            $variable->where('type', ProductType::Variable->value)
                ->whereDoesntHave('variants', function (Builder $variant) use ($backorder): void {
                    $variant->where(fn (Builder $available): Builder => $available
                        ->where('manage_stock', false)
                        ->orWhere('stock_quantity', '>', 0)
                        ->orWhere('stock_status', $backorder));
                });
        });
    }

    /**
     * Restrict the query to products with at least one purchasable unit.
     */
    public function scopeInStock(Builder $query): Builder
    {
        return $query->whereNot(fn (Builder $q): Builder => $q->outOfStock());
    }

    public function scopePublic(Builder $query): Builder
    {
        $query->active();

        if (! (bool) setting('shop.show_out_of_stock', false)) {
            $query->whereNot(fn (Builder $q): Builder => $q->outOfStock());
        }

        return $query;
    }

    /**
     * Display price: the configured price for simple products, the cheapest
     * variant price for variable products.
     */
    public function displayPrice(): ?string
    {
        if ($this->isVariable()) {
            $prices = $this->variants
                ->pluck('price')
                ->map(fn (mixed $price): float => (float) $price)
                ->filter(fn (float $price): bool => $price > 0);

            return $prices->isNotEmpty() ? (string) $prices->min() : $this->price;
        }

        return $this->price;
    }

    /**
     * Compare-at price for promotion display (simple products only).
     */
    public function displayCompareAtPrice(): ?string
    {
        return $this->isVariable() ? null : $this->compare_at_price;
    }

    /**
     * Whether the product carries a coherent promotion (compare-at above the
     * selling price).
     */
    public function isPromoted(): bool
    {
        $price = $this->displayPrice();
        $compare = $this->displayCompareAtPrice();

        return $price !== null && $compare !== null && (float) $compare > (float) $price;
    }

    /**
     * Promotional (HT) unit price produced by an active marketing promotion,
     * or null when none applies. Never modifies the stored price.
     */
    public function promoDisplayPrice(): ?float
    {
        return app(PromotionService::class)->promoPriceFor($this);
    }

    /**
     * Whether an active marketing promotion currently reduces this product.
     */
    public function hasActivePromotion(): bool
    {
        return $this->promoDisplayPrice() !== null;
    }

    /**
     * The running promotion responsible for the promo price, when any, used to
     * render a countdown on the storefront.
     */
    public function activePromotion(): ?\App\Models\Promotion
    {
        if ($this->isVariable()) {
            return null;
        }

        return app(PromotionService::class)->promoPromotionFor($this);
    }

    /**
     * Discount percentage offered by the active promotion, when any.
     */
    public function promoDiscountPercent(): ?int
    {
        $base = (float) $this->displayPrice();
        $promo = $this->promoDisplayPrice();

        if ($promo === null || $base <= 0 || $promo >= $base) {
            return null;
        }

        return (int) round(($base - $promo) / $base * 100);
    }

    /**
     * Percentage reduction of the promotion, when coherent.
     */
    public function discountPercent(): ?int
    {
        $price = $this->displayPrice();
        $compare = $this->displayCompareAtPrice();

        if ($price === null || $compare === null || (float) $compare <= (float) $price) {
            return null;
        }

        return (int) round(((float) $compare - (float) $price) / (float) $compare * 100);
    }

    /**
     * Whether at least one purchasable unit is currently available.
     */
    public function isAvailable(): bool
    {
        if ($this->isVariable()) {
            return $this->variants->contains(
                fn (ProductVariant $variant): bool => ! $variant->manage_stock
                    || $variant->stock_quantity > 0
                    || $variant->stock_status === StockStatus::OnBackorder,
            );
        }

        if (! $this->manage_stock || $this->stock_status === StockStatus::OnBackorder) {
            return true;
        }

        return $this->stock_quantity > 0;
    }

    /**
     * Product-level image paths (variant images excluded), ordered as stored.
     *
     * @return array<int, string>
     */
    public function getGalleryAttribute(): array
    {
        return $this->images
            ->filter(fn (ProductImage $image): bool => $image->product_variant_id === null)
            ->map(fn (ProductImage $image): string => (string) $image->path)
            ->values()
            ->all();
    }

    /**
     * Resolved public URL of the main product image, if any.
     */
    public function getPrimaryImageUrlAttribute(): ?string
    {
        $first = $this->gallery[0] ?? null;

        return $first !== null ? storefront_image($first) : null;
    }
}
