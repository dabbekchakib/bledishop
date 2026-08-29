<?php

namespace App\Filament\Resources\OrdersResource\Pages;

use App\Filament\Resources\OrdersResource;
use App\Filament\Resources\OrdersResource\Concerns\HasOrderStatusActions;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    use HasOrderStatusActions;

    protected static string $resource = OrdersResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...$this->getOrderStatusActions(),
            $this->getDeleteOrderAction()
                ->visible(fn (): bool => auth()->user()?->can('orders.delete') ?? false),
        ];
    }
}
