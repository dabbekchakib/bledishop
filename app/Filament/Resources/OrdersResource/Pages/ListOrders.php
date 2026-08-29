<?php

namespace App\Filament\Resources\OrdersResource\Pages;

use App\Filament\Resources\OrdersResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListOrders extends ListRecords
{
    protected static string $resource = OrdersResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label(__('admin.orders.export'))
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->visible(fn (): bool => auth()->user()?->can('orders.export') ?? false)
                ->url(fn (): string => $this->exportUrl()),
        ];
    }

    /**
     * Build the CSV export URL, carrying over the filters currently applied
     * to the table so that the exported selection matches what is shown.
     */
    protected function exportUrl(): string
    {
        $filters = $this->tableFilters ?? [];
        $search = $this->getTableSearch();

        $params = [];
        $status = $filters['status']['value'] ?? null;
        if ($status !== null) {
            $params['status'] = $status;
        }

        $payment = $filters['payment_status']['value'] ?? null;
        if ($payment !== null) {
            $params['payment_status'] = $payment;
        }

        $type = $filters['customer_type']['value'] ?? null;
        if ($type !== null) {
            $params['customer_type'] = $type;
        }

        $from = $filters['date']['from'] ?? null;
        if (filled($from)) {
            $params['from'] = $from;
        }

        $until = $filters['date']['until'] ?? null;
        if (filled($until)) {
            $params['until'] = $until;
        }

        $min = $filters['total']['min'] ?? null;
        if ($min !== null && $min !== '') {
            $params['min_total'] = $min;
        }

        $max = $filters['total']['max'] ?? null;
        if ($max !== null && $max !== '') {
            $params['max_total'] = $max;
        }

        if (filled($search)) {
            $params['search'] = $search;
        }

        return route('admin.orders.export', $params);
    }
}
