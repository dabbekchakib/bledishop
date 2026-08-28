<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\Concerns\SyncsProduct;
use App\Filament\Resources\ProductResource;
use App\Services\ProductService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    use SyncsProduct;

    protected static string $resource = ProductResource::class;

    protected function productService(): object
    {
        return app(ProductService::class);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->requiresConfirmation(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
