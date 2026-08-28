<?php

namespace App\Models;

use App\Enums\AttributeType;
use App\Enums\ContentStatus;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attribute extends Model
{
    use HasFactory;
    use HasTranslations;
    use SoftDeletes;

    protected $fillable = [
        'code',
        'type',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'type' => AttributeType::class,
        'status' => ContentStatus::class,
        'sort_order' => 'integer',
    ];

    public static function translationModel(): string
    {
        return AttributeTranslation::class;
    }

    public function translations(): HasMany
    {
        return $this->hasMany(AttributeTranslation::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class)->orderBy('sort_order')->orderBy('id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'attribute_product');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ContentStatus::Active->value);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }
}
