<?php

namespace App\Filament\Resources\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Bridges the attribute form (translations and values under dedicated keys)
 * with the AttributeService lifecycle.
 */
trait SyncsAttribute
{
    protected ?array $attributeTranslations = null;

    protected ?array $attributeValues = null;

    /**
     * @return object the attribute service responsible for the attribute lifecycle
     */
    abstract protected function attributeService(): object;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['translations'] = $this->attributeService()->translationFormData($this->record);

        $data['values'] = $this->record->values
            ->map(fn (Model $value): array => [
                'id' => $value->id,
                'value' => $value->value,
                'color_code' => $value->color_code,
                'sort_order' => $value->sort_order,
                'status_is_active' => $value->status->isActive(),
                'translations' => $value->translations
                    ->mapWithKeys(fn (Model $t): array => [
                        $t->locale => ['label' => $t->label],
                    ])
                    ->all(),
            ])
            ->all();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->captureAttributeDependencies($data);

        return $this->stripAttributeKeys($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->captureAttributeDependencies($data);

        return $this->stripAttributeKeys($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function captureAttributeDependencies(array $data): void
    {
        $this->attributeTranslations = $this->arrayOf($data['translations'] ?? []);
        $this->attributeValues = $this->arrayOf($data['values'] ?? []);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function stripAttributeKeys(array $data): array
    {
        foreach (['translations', 'values'] as $key) {
            unset($data[$key]);
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return $this->attributeService()->create(
            $data,
            $this->attributeTranslations ?? [],
            $this->attributeValues ?? [],
        );
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return $this->attributeService()->update(
            $record,
            $data,
            $this->attributeTranslations ?? [],
            $this->attributeValues ?? [],
        );
    }

    /**
     * @return array<int|string, mixed>
     */
    private function arrayOf(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }
}
