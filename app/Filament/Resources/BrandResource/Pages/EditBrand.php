<?php

namespace App\Filament\Resources\BrandResource\Pages;

use App\Filament\Resources\BrandResource;
use App\Filament\Resources\Concerns\SyncsCatalogTranslations;
use App\Services\BrandService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBrand extends EditRecord
{
    use SyncsCatalogTranslations;

    protected static string $resource = BrandResource::class;

    protected function catalogService(): object
    {
        return app(BrandService::class);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->requiresConfirmation(),
        ];
    }
}
