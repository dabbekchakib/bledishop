<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read ?Model $target the entity this item links to (page/category/product/brand)
 */
class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'menu_id',
        'parent_id',
        'type',
        'label',
        'target_url',
        'target_id',
        'is_external',
        'target_blank',
        'css_class',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'target_id' => 'integer',
        'label' => 'array',
        'is_external' => 'boolean',
        'target_blank' => 'boolean',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->ordered()->active();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * The custom admin label for a locale, falling back to the entity's
     * translated name, then to the default locale, then any value.
     */
    public function labelFor(?string $locale = null): string
    {
        $locale ??= current_locale();

        $labels = is_array($this->label) ? $this->label : [];

        if (filled($labels[$locale] ?? null)) {
            return (string) $labels[$locale];
        }

        $default = (string) config('app.fallback_locale', 'fr');

        if (filled($labels[$default] ?? null)) {
            return (string) $labels[$default];
        }

        foreach ($labels as $value) {
            if (filled($value)) {
                return (string) $value;
            }
        }

        return (string) $this->targetName($locale);
    }

    /**
     * Resolve the destination URL for a locale.
     */
    public function urlFor(?string $locale = null): string
    {
        $locale ??= current_locale();

        return match ($this->type) {
            'page' => $this->target?->exists()
                ? localized_route('shop.page.show', ['slug' => $this->target->translatedSlug($locale)], $locale)
                : '#',
            'category' => $this->target?->exists()
                ? localized_route('shop.category.show', ['slug' => $this->target->translatedSlug($locale)], $locale)
                : '#',
            'product' => $this->target?->exists()
                ? localized_route('shop.product.show', ['slug' => $this->target->translatedSlug($locale)], $locale)
                : '#',
            'brand' => $this->target?->exists()
                ? localized_route('shop.brand.show', ['slug' => $this->target->translatedSlug($locale)], $locale)
                : '#',
            default => (string) ($this->target_url ?? '#'),
        };
    }

    /**
     * A human label derived from the linked entity, used as a fallback when the
     * admin did not provide a custom per-locale label.
     */
    public function targetName(?string $locale = null): string
    {
        return match ($this->type) {
            'page' => $this->target?->translatedTitle($locale) ?? '',
            'category' => $this->target?->translatedName($locale) ?? '',
            'product' => $this->target?->translatedName($locale) ?? '',
            'brand' => $this->target?->translatedName($locale) ?? '',
            default => (string) ($this->target_url ?? ''),
        };
    }

    /**
     * Polymorphic-ish accessor: the entity this item points to, based on type.
     */
    public function getTargetAttribute(): ?Model
    {
        return match ($this->type) {
            'page' => Page::find($this->target_id),
            'category' => Category::find($this->target_id),
            'product' => Product::find($this->target_id),
            'brand' => Brand::find($this->target_id),
            default => null,
        };
    }
}
