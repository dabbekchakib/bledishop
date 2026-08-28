<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\Concerns\SyncsCatalogTranslations;
use App\Services\CategoryService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    use SyncsCatalogTranslations;

    protected static string $resource = CategoryResource::class;

    protected function catalogService(): object
    {
        return app(CategoryService::class);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->requiresConfirmation(),
        ];
    }
}
