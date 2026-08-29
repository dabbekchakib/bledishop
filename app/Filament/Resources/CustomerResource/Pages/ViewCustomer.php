<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Enums\Role as UserRole;
use App\Filament\Resources\CustomerResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected string $view = 'filament.resources.customers.view';

    protected function getHeaderActions(): array
    {
        $record = $this->getRecord();

        return [
            Action::make('toggle_active')
                ->label($record->is_active
                    ? __('admin.customers.deactivate')
                    : __('admin.customers.activate'))
                ->icon($record->is_active
                    ? Heroicon::OutlinedUserMinus
                    : Heroicon::OutlinedCheckCircle)
                ->color($record->is_active ? 'danger' : 'success')
                ->requiresConfirmation()
                ->modalHeading($record->is_active
                    ? __('admin.customers.deactivate')
                    : __('admin.customers.activate'))
                ->modalSubmitActionLabel($record->is_active
                    ? __('admin.customers.deactivate')
                    : __('admin.customers.activate'))
                ->modalDescription($record->is_active
                    ? __('admin.customers.deactivate_confirmation', ['name' => $record->fullName()])
                    : __('admin.customers.activate_confirmation', ['name' => $record->fullName()]))
                ->successNotificationTitle($record->is_active
                    ? __('admin.customers.deactivate').' ✓'
                    : __('admin.customers.activate').' ✓')
                ->action(function () use ($record): void {
                    $record->update(['is_active' => ! $record->is_active]);
                })
                ->visible(fn (): bool => (bool) (auth()->user()?->can('customers.activate')))
                ->disabled(fn (): bool => $this->record->hasRole(UserRole::SuperAdmin->value)),
            EditAction::make()
                ->visible(fn (): bool => (bool) (auth()->user()?->can('customers.update'))),
        ];
    }
}
