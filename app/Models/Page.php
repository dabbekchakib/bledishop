<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $fillable = [
        'template',
        'is_active',
        'published_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    public static function translationModel(): string
    {
        return PageTranslation::class;
    }

    public function translations(): HasMany
    {
        return $this->hasMany(PageTranslation::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->active()->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->published();
    }

    public function translatedTitle(?string $locale = null): string
    {
        return (string) ($this->translation($locale)?->title ?? '');
    }

    public function translatedExcerpt(?string $locale = null): string
    {
        return (string) ($this->translation($locale)?->excerpt ?? '');
    }

    public function translatedContent(?string $locale = null): string
    {
        return (string) ($this->translation($locale)?->content ?? '');
    }
}
