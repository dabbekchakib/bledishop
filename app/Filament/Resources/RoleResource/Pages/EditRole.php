<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Enums\Role as UserRole;
use App\Filament\Resources\RoleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\Models\Role as SpatieRole;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn (SpatieRole $record): bool => $record->name === UserRole::SuperAdmin->value)
                ->requiresConfirmation(),
        ];
    }
}
