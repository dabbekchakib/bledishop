<?php

namespace App\Services;

use App\Enums\AttributeType;
use App\Models\Attribute;
use App\Models\AttributeTranslation;
use App\Models\AttributeValueTranslation;
use App\Services\Concerns\SynchronizesCatalogTranslations;
use App\Support\Slugger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttributeService
{
    use SynchronizesCatalogTranslations;

    protected function translationModel(): string
    {
        return AttributeTranslation::class;
    }

    protected function translationForeignKey(): string
    {
        return 'attribute_id';
    }

    public function create(array $attributes, array $translations, array $values = []): Attribute
    {
        $this->assertDefaultTranslationPresent($translations);
        $this->assertCodeAvailable($attributes['code'] ?? null, null);

        $attribute = DB::transaction(function () use ($attributes, $translations, $values): Attribute {
            $attribute = Attribute::create($attributes);

            $this->syncTranslations($attribute, $translations);
            $this->syncValues($attribute, $values);

            return $attribute;
        });

        return $attribute->fresh(['translations', 'values.translations']);
    }

    public function update(Attribute $attribute, array $attributes, array $translations, array $values = []): Attribute
    {
        $this->assertDefaultTranslationPresent($translations);
        $this->assertCodeAvailable($attributes['code'] ?? $attribute->code, $attribute->id);

        DB::transaction(function () use ($attribute, $attributes, $translations, $values): void {
            $attribute->update($attributes);

            $this->syncTranslations($attribute, $translations);
            $this->syncValues($attribute, $values);
        });

        return $attribute->fresh(['translations', 'values.translations']);
    }

    /**
     * Synchronize the multilingual names of an attribute.
     *
     * @param  array<string, array<string, mixed>>  $translations
     */
    public function syncTranslations(Model $entity, array $translations): void
    {
        foreach ($this->enabledLocales() as $locale) {
            $name = trim((string) ($translations[$locale]['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            AttributeTranslation::updateOrCreate(
                [
                    'attribute_id' => $entity->id,
                    'locale' => $locale,
                ],
                ['name' => $name],
            );
        }
    }

    public function translationFormData(Model $entity): array
    {
        $data = [];

        foreach ($this->enabledLocales() as $locale) {
            $translation = $entity->translations()->where('locale', $locale)->first();

            $data[$locale] = $translation?->only(['name']) ?? [];
        }

        return $data;
    }

    /**
     * Replace the values of an attribute.
     *
     * @param  array<int, array<string, mixed>>  $values
     */
    public function syncValues(Attribute $attribute, array $values): void
    {
        $attribute->values()->forceDelete();

        $seen = [];

        foreach ($values as $value) {
            $raw = trim((string) ($value['value'] ?? ''));

            if ($raw === '') {
                continue;
            }

            $key = mb_strtolower($raw);

            if (isset($seen[$key])) {
                throw ValidationException::withMessages([
                    'values' => 'La valeur "'.$raw.'" est saisie plusieurs fois.',
                ]);
            }
            $seen[$key] = true;

            $colorCode = null;
            if ($attribute->type === AttributeType::Color && ! empty($value['color_code'])) {
                $colorCode = ltrim((string) $value['color_code'], '#');
            }

            $saved = $attribute->values()->create([
                'value' => $raw,
                'color_code' => $colorCode,
                'sort_order' => (int) ($value['sort_order'] ?? 0),
                'status' => filter_var($value['status_is_active'] ?? true, FILTER_VALIDATE_BOOLEAN)
                    ? 'active'
                    : 'inactive',
            ]);

            if (isset($value['translations']) && is_array($value['translations'])) {
                foreach ($this->enabledLocales() as $locale) {
                    $label = trim((string) ($value['translations'][$locale]['label'] ?? ''));

                    if ($label === '') {
                        continue;
                    }

                    AttributeValueTranslation::updateOrCreate(
                        [
                            'attribute_value_id' => $saved->id,
                            'locale' => $locale,
                        ],
                        ['label' => $label],
                    );
                }
            }
        }
    }

    public function assertCodeAvailable(?string $code, ?int $ignoreId): void
    {
        if ($code === null || trim($code) === '') {
            throw ValidationException::withMessages([
                'code' => 'Le code est obligatoire.',
            ]);
        }

        $slug = Slugger::make($code, 'en');
        $exists = Attribute::query()
            ->where('code', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->withTrashed()
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'code' => 'Ce code est déjà utilisé.',
            ]);
        }
    }
}
