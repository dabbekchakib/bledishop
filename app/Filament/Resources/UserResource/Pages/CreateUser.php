<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\Concerns\NotifiesCreatedRecords;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Concerns\SyncsRoles;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    use NotifiesCreatedRecords;
    use SyncsRoles;

    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        $this->record->syncRoles($this->roleIds());
        $this->notifyCreatedRecord();
    }
}
