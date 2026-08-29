<?php

namespace App\Filament\Resources\OrdersResource\Pages;

use App\Filament\Resources\OrdersResource;
use App\Filament\Resources\OrdersResource\Concerns\HasOrderStatusActions;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewOrder extends ViewRecord
{
    use HasOrderStatusActions;

    protected static string $resource = OrdersResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label(__('admin.orders.print'))
                ->icon(Heroicon::OutlinedPrinter)
                ->color('gray')
                ->visible(fn (): bool => auth()->user()?->can('orders.print') ?? false)
                ->url(fn (): string => route('admin.orders.print', ['order' => $this->getRecord()]))
                ->openUrlInNewTab(),
            ...$this->getOrderStatusActions(),
            EditAction::make()
                ->visible(fn (): bool => auth()->user()?->can('orders.update') ?? false),
        ];
    }
}
