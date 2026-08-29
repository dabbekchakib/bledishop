<?php

namespace App\Filament\Resources\Concerns;

use App\Services\MenuService;

/**
 * Bridges the nested "items" repeater of the Menu form with MenuService so the
 * full item tree (including hierarchy, ordering and removed items) is
 * synchronised right after the menu record is persisted.
 */
trait SyncsMenuItems
{
    protected ?array $menuItems = null;

    protected function menuService(): MenuService
    {
        return app(MenuService::class);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['items'] = $this->menuService()->hydrateItems($this->record);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->stripItems($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->stripItems($data);
    }

    protected function afterCreate(): void
    {
        $this->menuService()->syncItems($this->record, $this->menuItems ?? []);
    }

    protected function afterSave(): void
    {
        $this->menuService()->syncItems($this->record, $this->menuItems ?? []);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function stripItems(array $data): array
    {
        $this->menuItems = $data['items'] ?? [];
        unset($data['items']);

        return $data;
    }
}
