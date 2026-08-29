<?php

namespace App\Filament\Resources\MenuResource\Pages;

use App\Filament\Resources\Concerns\SyncsMenuItems;
use App\Filament\Resources\MenuResource;
use App\Services\MenuService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMenu extends EditRecord
{
    use SyncsMenuItems;

    protected static string $resource = MenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->requiresConfirmation()
                ->after(fn () => app(MenuService::class)->clearCache()),
        ];
    }
}
