<?php

namespace App\Filament\Resources\MenuResource\Pages;

use App\Filament\Resources\Concerns\SyncsMenuItems;
use App\Filament\Resources\MenuResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMenu extends CreateRecord
{
    use SyncsMenuItems;

    protected static string $resource = MenuResource::class;
}
