<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory;
    use HasTranslations;
    use SoftDeletes;

    protected $fillable = [
        'parent_id',
        'icon',
        'image',
        'status',
        'sort_order',
        'is_featured',
    ];

    protected $casts = [
        'status' => ContentStatus::class,
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
    ];

    public static function translationModel(): string
    {
        return CategoryTranslation::class;
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(CategoryTranslation::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ContentStatus::Active->value);
    }

    public function scopeFeatured(Builder $query, bool $featured = true): Builder
    {
        return $query->where('is_featured', $featured);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->active();
    }

    /**
     * Whether the given id is an acceptable parent: no self-parenting and no
     * cycle (a category cannot become a descendant of itself).
     */
    public function isAllowedParent(?int $parentId): bool
    {
        if ($parentId === null) {
            return true;
        }

        if ($this->exists && $parentId === $this->id) {
            return false;
        }

        $cursorId = $parentId;

        while ($cursorId !== null) {
            if ($this->exists && $cursorId === $this->id) {
                return false;
            }

            $cursorId = (int) self::query()->whereKey($cursorId)->value('parent_id');
        }

        return true;
    }

    /**
     * Ids of every descendant category (all levels below this one).
     *
     * @return array<int, int>
     */
    public function descendantIds(): array
    {
        $ids = [];
        $frontier = [$this->id];

        while ($frontier !== []) {
            $children = self::query()
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->all();

            $ids = array_merge($ids, $children);
            $frontier = $children;
        }

        return $ids;
    }

    protected static function booted(): void
    {
        static::deleting(function (Category $category): void {
            if ($category->isForceDeleting()) {
                return;
            }

            self::query()
                ->where('parent_id', $category->id)
                ->update(['parent_id' => null]);
        });
    }
}
