<?php

namespace App\Filament\Resources\BrandResource\Pages;

use App\Filament\Resources\BrandResource;
use App\Filament\Resources\Concerns\NotifiesCreatedRecords;
use App\Filament\Resources\Concerns\SyncsCatalogTranslations;
use App\Services\BrandService;
use Filament\Resources\Pages\CreateRecord;

class CreateBrand extends CreateRecord
{
    use NotifiesCreatedRecords;
    use SyncsCatalogTranslations;

    protected static string $resource = BrandResource::class;

    protected function catalogService(): object
    {
        return app(BrandService::class);
    }
}
