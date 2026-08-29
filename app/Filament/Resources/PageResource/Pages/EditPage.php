<?php

namespace App\Filament\Resources\PageResource\Pages;

use App\Filament\Resources\Concerns\SyncsCatalogTranslations;
use App\Filament\Resources\PageResource;
use App\Services\PageService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPage extends EditRecord
{
    use SyncsCatalogTranslations;

    protected static string $resource = PageResource::class;

    protected function catalogService(): object
    {
        return app(PageService::class);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->requiresConfirmation(),
        ];
    }
}
