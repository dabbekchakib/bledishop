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

class AttributeValue extends Model
{
    use HasFactory;
    use HasTranslations;
    use SoftDeletes;

    protected $fillable = [
        'attribute_id',
        'value',
        'color_code',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'status' => ContentStatus::class,
        'sort_order' => 'integer',
    ];

    public static function translationModel(): string
    {
        return AttributeValueTranslation::class;
    }

    public function translations(): HasMany
    {
        return $this->hasMany(AttributeValueTranslation::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ContentStatus::Active->value);
    }

    /**
     * Localized label, falling back to the raw value when no translation exists.
     */
    public function translatedLabel(?string $locale = null): string
    {
        return (string) ($this->translation($locale)?->label ?? $this->value ?? '');
    }
}
