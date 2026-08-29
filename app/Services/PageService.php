<?php

namespace App\Services;

use App\Models\Page;
use App\Models\PageTranslation;
use App\Support\Sanitizer;
use App\Support\Slugger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class PageService
{
    public function __construct(private readonly LocalizationService $localizer) {}

    /**
     * @return array<int, string>
     */
    public function enabledLocales(): array
    {
        return $this->localizer->availableLocales();
    }

    public function create(array $attributes, array $translations): Page
    {
        $this->validateForCreate($attributes, $translations);

        $page = Page::create($attributes);

        $this->syncTranslations($page, $translations);

        return $page;
    }

    public function update(Model $page, array $attributes, array $translations): Page
    {
        /** @var Page $page */
        $this->validateForUpdate($page, $attributes, $translations);

        $page->update($attributes);

        $this->syncTranslations($page, $translations);

        return $page;
    }

    public function delete(Model $page): void
    {
        /** @var Page $page */
        $page->delete();
    }

    public function validateForCreate(array $attributes, array $translations): void
    {
        $this->assertDefaultTranslationPresent($translations);
    }

    public function validateForUpdate(Model $page, array $attributes, array $translations): void
    {
        $this->assertDefaultTranslationPresent($translations);
    }

    /**
     * The default locale translation is mandatory so every page always has a
     * usable fallback.
     *
     * @param  array<string, array<string, mixed>>  $translations
     */
    public function assertDefaultTranslationPresent(array $translations): void
    {
        $default = $this->localizer->defaultLocale();
        $title = trim((string) ($translations[$default]['title'] ?? ''));

        if ($title === '') {
            throw ValidationException::withMessages([
                "translations.{$default}.title" => __('admin.pages.title_required_in_default_language', ['locale' => $default]),
            ]);
        }
    }

    /**
     * Persist (create or update) the translations of a page. Locales without a
     * title are ignored; a slug is generated from the title when empty and a
     * collision always receives a numeric suffix.
     *
     * @param  array<string, array<string, mixed>>  $translations  locale => fields
     */
    public function syncTranslations(Model $page, array $translations): void
    {
        /** @var Page $page */
        foreach ($this->enabledLocales() as $locale) {
            $fields = $translations[$locale] ?? [];
            $title = trim((string) ($fields['title'] ?? ''));

            if ($title === '') {
                continue;
            }

            $customSlug = trim((string) ($fields['slug'] ?? ''));
            $slug = Slugger::unique(
                $customSlug !== '' ? Slugger::make($customSlug, $locale) : Slugger::make($title, $locale),
                $locale,
                (new PageTranslation)->getTable(),
                $this->existingTranslationId($page, $locale),
            );

            PageTranslation::updateOrCreate(
                [
                    'page_id' => $page->id,
                    'locale' => $locale,
                ],
                [
                    'title' => $title,
                    'slug' => $slug,
                    'excerpt' => trim((string) ($fields['excerpt'] ?? '')),
                    'content' => Sanitizer::clean((string) ($fields['content'] ?? '')),
                    'meta_title' => trim((string) ($fields['meta_title'] ?? '')),
                    'meta_description' => trim((string) ($fields['meta_description'] ?? '')),
                    'meta_keywords' => trim((string) ($fields['meta_keywords'] ?? '')),
                    'robots' => $fields['robots'] ?? null,
                    'og_image' => trim((string) ($fields['og_image'] ?? '')),
                ],
            );
        }
    }

    /**
     * Helper for the admin pages: build the "translations" form data from an
     * existing record.
     */
    public function translationFormData(Model $page): array
    {
        $data = [];

        foreach ($this->enabledLocales() as $locale) {
            $translation = $page->translations()->where('locale', $locale)->first();

            $data[$locale] = $translation?->only([
                'title',
                'slug',
                'excerpt',
                'content',
                'meta_title',
                'meta_description',
                'meta_keywords',
                'robots',
                'og_image',
            ]) ?? [];
        }

        return $data;
    }

    /**
     * Find a published page by slug for the given (or current) locale.
     */
    public function findPublishedBySlug(string $slug, ?string $locale = null): ?Page
    {
        $locale ??= $this->localizer->currentLocale();

        return Page::query()
            ->public()
            ->whereHas('translations', static fn ($q) => $q
                ->where('locale', $locale)
                ->where('slug', $slug))
            ->first();
    }

    private function existingTranslationId(Model $page, string $locale): ?int
    {
        return (int) PageTranslation::query()
            ->where('page_id', $page->id)
            ->where('locale', $locale)
            ->value('id') ?: null;
    }
}
