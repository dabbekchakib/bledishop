<?php

namespace App\Filament\Resources\RedirectResource\Pages;

use App\Filament\Resources\Concerns\NotifiesCreatedRecords;
use App\Filament\Resources\RedirectResource;
use App\Services\RedirectService;
use Filament\Resources\Pages\CreateRecord;

class CreateRedirect extends CreateRecord
{
    use NotifiesCreatedRecords;

    protected static string $resource = RedirectResource::class;

    protected function afterCreate(): void
    {
        app(RedirectService::class)->clearCache();
        $this->notifyCreatedRecord();
    }
}
