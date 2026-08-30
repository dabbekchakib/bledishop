<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\Concerns\NotifiesCreatedRecords;
use App\Filament\Resources\Concerns\SyncsProduct;
use App\Filament\Resources\ProductResource;
use App\Services\ProductService;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    use NotifiesCreatedRecords;
    use SyncsProduct;

    protected static string $resource = ProductResource::class;

    protected function productService(): object
    {
        return app(ProductService::class);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $this->notifyCreatedRecord();
    }
}
