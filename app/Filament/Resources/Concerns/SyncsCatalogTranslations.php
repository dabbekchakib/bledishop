<?php

namespace App\Filament\Resources\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Bridges the Filament form (grouped under a "translations" key) with the
 * catalog services. The translation payload is extracted before the model is
 * persisted and synchronised right after, so a failed validation never leaves
 * orphaned translation rows.
 */
trait SyncsCatalogTranslations
{
    protected ?array $catalogTranslations = null;

    /**
     * @return object the catalog service handling this resource model
     */
    abstract protected function catalogService(): object;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, array<string, mixed>>
     */
    protected function extractCatalogTranslations(array &$data): array
    {
        $translations = $data['translations'] ?? [];

        unset($data['translations']);

        return is_array($translations) ? $translations : [];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['translations'] = $this->catalogService()->translationFormData($this->record);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = $this->stripCatalogTranslations($data);
        $this->catalogService()->validateForCreate($data, $this->catalogTranslations);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = $this->stripCatalogTranslations($data);
        $this->catalogService()->validateForUpdate($this->record, $data, $this->catalogTranslations);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->catalogService()->syncTranslations($this->record, $this->catalogTranslations ?? []);

        if (method_exists($this, 'notifyCreatedRecord')) {
            $this->notifyCreatedRecord();
        }
    }

    protected function afterSave(): void
    {
        $this->catalogService()->syncTranslations($this->record, $this->catalogTranslations ?? []);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function stripCatalogTranslations(array $data): array
    {
        $this->catalogTranslations = $this->extractCatalogTranslations($data);

        return $data;
    }
}
