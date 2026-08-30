<?php

namespace App\Filament\Resources\OrdersResource\Concerns;

use App\Enums\OrderStatus;
use App\Filament\Resources\OrdersResource;
use App\Services\OrderStatusService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;

/**
 * Provides the quick status-transition actions used on the order View and
 * Edit pages. Every transition goes through OrderStatusService so it is
 * validated, recorded in the history and idempotent for stock.
 */
trait HasOrderStatusActions
{
    protected function getOrderStatusActions(): array
    {
        if (! auth()->user()?->can('orders.change_status')) {
            return [];
        }

        $service = app(OrderStatusService::class);
        $record = $this->getRecord();

        return collect($service->allowedTargets($record))
            ->map(function (string $label, string $value) use ($service, $record): Action {
                $color = match ($value) {
                    'confirmed' => Color::Blue,
                    'processing' => Color::Amber,
                    'shipped' => Color::Blue,
                    'delivered' => Color::Green,
                    'cancelled' => Color::Red,
                    'on_hold' => Color::Orange,
                    'pending' => Color::Gray,
                    default => Color::Gray,
                };

                $confirmation = $value === 'cancelled'
                    ? __('admin.orders.cancel_confirmation', ['order' => $record->order_number])
                    : __('admin.orders.transition_confirmation', ['to' => $label, 'order' => $record->order_number]);

                return Action::make('set_status_'.$value)
                    ->label($label)
                    ->color($color)
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->requiresConfirmation()
                    ->modalHeading($label)
                    ->modalDescription($confirmation)
                    ->action(function () use ($service, $record, $value, $label): void {
                        $service->transition($record, OrderStatus::tryFrom($value), auth()->user());

                        Notification::make()
                            ->title(__('admin.orders.status_updated', ['status' => $label]))
                            ->success()
                            ->send();

                        $this->redirect(OrdersResource::getUrl('view', ['record' => $record]));
                    });
            })
            ->values()
            ->all();
    }

    protected function getDeleteOrderAction(): Action
    {
        return DeleteAction::make()
            ->requiresConfirmation()
            ->modalHeading(__('admin.orders.delete_title'))
            ->modalDescription(__('admin.orders.delete_confirmation'));
    }
}
