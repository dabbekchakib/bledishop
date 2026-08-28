<?php

namespace App\Models\Concerns;

use App\Services\LocalizationService;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared multilingual behaviour for catalog entities (categories, brands).
 *
 * Translation resolution order:
 *  1. the requested locale (defaults to the current application locale),
 *  2. the configured default locale,
 *  3. the first available translation.
 */
trait HasTranslations
{
    /**
     * @return string the translation model class for this entity
     */
    abstract public static function translationModel(): string;

    public function translation(?string $locale = null): ?Model
    {
        $locale ??= app()->getLocale();

        if (! $this->relationLoaded('translations')) {
            $this->setRelation('translations', $this->translations()->get());
        }

        $translations = $this->translations;

        return $translations->first(static fn (Model $translation): bool => (string) $translation->locale === $locale)
            ?? $translations->first(static fn (Model $translation): bool => (string) $translation->locale === app(LocalizationService::class)->defaultLocale())
            ?? $translations->first();
    }

    public function translatedName(?string $locale = null): string
    {
        return (string) ($this->translation($locale)?->name ?? '');
    }

    public function translatedSlug(?string $locale = null): string
    {
        return (string) ($this->translation($locale)?->slug ?? '');
    }

    public function translatedDescription(?string $locale = null): string
    {
        return (string) ($this->translation($locale)?->description ?? '');
    }
}
