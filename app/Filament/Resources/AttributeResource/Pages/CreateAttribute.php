<?php

namespace App\Filament\Resources\AttributeResource\Pages;

use App\Filament\Resources\AttributeResource;
use App\Filament\Resources\Concerns\SyncsAttribute;
use App\Services\AttributeService;
use Filament\Resources\Pages\CreateRecord;

class CreateAttribute extends CreateRecord
{
    use SyncsAttribute;

    protected static string $resource = AttributeResource::class;

    protected function attributeService(): object
    {
        return app(AttributeService::class);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
