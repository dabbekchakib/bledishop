<?php

namespace App\Services\Concerns;

use App\Services\LocalizationService;
use App\Support\Slugger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * Shared persistence logic for multilingual catalog entities.
 */
trait SynchronizesCatalogTranslations
{
    /**
     * @return string the translation model FQCN
     */
    abstract protected function translationModel(): string;

    /**
     * @return string the foreign key column used by the translation model
     */
    abstract protected function translationForeignKey(): string;

    protected function localizer(): LocalizationService
    {
        return app(LocalizationService::class);
    }

    /**
     * @return array<int, string>
     */
    public function enabledLocales(): array
    {
        return $this->localizer()->availableLocales();
    }

    /**
     * Persist (create or update) the translations of an entity. Locales without
     * a name are ignored; a slug is generated from the name when empty, and a
     * collision always receives a numeric suffix. Custom slugs are never
     * overwritten.
     *
     * @param  array<string, array<string, mixed>>  $translations  locale => fields
     */
    public function syncTranslations(Model $entity, array $translations): void
    {
        $model = $this->translationModel();
        $table = (new $model)->getTable();

        foreach ($this->enabledLocales() as $locale) {
            $fields = $translations[$locale] ?? [];
            $name = trim((string) ($fields['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $customSlug = trim((string) ($fields['slug'] ?? ''));
            $slug = Slugger::unique(
                $customSlug !== '' ? Slugger::make($customSlug, $locale) : Slugger::make($name, $locale),
                $locale,
                $table,
                $this->existingTranslationId($entity, $locale),
            );

            $model::updateOrCreate(
                [
                    $this->translationForeignKey() => $entity->id,
                    'locale' => $locale,
                ],
                [
                    'name' => $name,
                    'slug' => $slug,
                    'description' => trim((string) ($fields['description'] ?? '')),
                    'meta_title' => trim((string) ($fields['meta_title'] ?? '')),
                    'meta_description' => trim((string) ($fields['meta_description'] ?? '')),
                    'meta_keywords' => trim((string) ($fields['meta_keywords'] ?? '')),
                ],
            );
        }
    }

    /**
     * The default locale translation is mandatory so the catalog always has a
     * usable fallback.
     *
     * @param  array<string, array<string, mixed>>  $translations
     */
    public function assertDefaultTranslationPresent(array $translations): void
    {
        $default = $this->localizer()->defaultLocale();
        $name = trim((string) ($translations[$default]['name'] ?? ''));

        if ($name === '') {
            throw ValidationException::withMessages([
                "translations.{$default}.name" => 'Le nom est obligatoire dans la langue par défaut.',
            ]);
        }
    }

    /**
     * Helper for the admin pages: build the "translations" form data from an
     * existing record.
     */
    public function translationFormData(Model $entity): array
    {
        $data = [];

        foreach ($this->enabledLocales() as $locale) {
            $translation = $entity->translations()->where('locale', $locale)->first();

            $data[$locale] = $translation?->only([
                'name',
                'slug',
                'description',
                'meta_title',
                'meta_description',
                'meta_keywords',
            ]) ?? [];
        }

        return $data;
    }

    private function existingTranslationId(Model $entity, string $locale): ?int
    {
        $model = $this->translationModel();

        return (int) $model::query()
            ->where($this->translationForeignKey(), $entity->id)
            ->where('locale', $locale)
            ->value('id') ?: null;
    }
}
