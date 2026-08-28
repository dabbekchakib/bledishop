<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Enums\Role as UserRole;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Concerns\SyncsRoles;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\Models\Role as SpatieRole;

class EditUser extends EditRecord
{
    use SyncsRoles;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->requiresConfirmation(),
        ];
    }

    protected function afterSave(): void
    {
        $this->record->syncRoles($this->roleIds());
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $auth = auth()->user();

        if (! $auth instanceof User || ! $auth->is($this->record)) {
            return $data;
        }

        $data['is_active'] = true;

        if ($auth->isSuperAdmin()) {
            $superAdminRole = SpatieRole::findByName(UserRole::SuperAdmin->value);

            $data['roles'] = array_merge(
                array_map('strval', $data['roles'] ?? []),
                [(string) $superAdminRole->id],
            );
        }

        return $data;
    }
}
