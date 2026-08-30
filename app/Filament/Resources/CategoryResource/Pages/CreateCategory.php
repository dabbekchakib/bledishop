<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\Concerns\NotifiesCreatedRecords;
use App\Filament\Resources\Concerns\SyncsCatalogTranslations;
use App\Services\CategoryService;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    use NotifiesCreatedRecords;
    use SyncsCatalogTranslations;

    protected static string $resource = CategoryResource::class;

    protected function catalogService(): object
    {
        return app(CategoryService::class);
    }
}
