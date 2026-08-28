<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use HasFactory;
    use HasTranslations;
    use SoftDeletes;

    protected $fillable = [
        'logo',
        'website',
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
        return BrandTranslation::class;
    }

    public function translations(): HasMany
    {
        return $this->hasMany(BrandTranslation::class);
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
}
