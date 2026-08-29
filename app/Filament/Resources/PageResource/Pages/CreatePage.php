<?php

namespace App\Filament\Resources\PageResource\Pages;

use App\Filament\Resources\Concerns\SyncsCatalogTranslations;
use App\Filament\Resources\PageResource;
use App\Services\PageService;
use Filament\Resources\Pages\CreateRecord;

class CreatePage extends CreateRecord
{
    use SyncsCatalogTranslations;

    protected static string $resource = PageResource::class;

    protected function catalogService(): object
    {
        return app(PageService::class);
    }
}
