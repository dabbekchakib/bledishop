<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Filament\Widgets\Concerns\HasDashboardPeriod;
use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestOrdersTable extends TableWidget
{
    use HasDashboardPeriod;

    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('orders.view') ?? false;
    }

    public function getTableHeading(): string
    {
        return __('admin.dashboard.latest_orders');
    }

    public function table(Table $table): Table
    {
        [$start, $end] = $this->dashboardRange();

        return $table
            ->query(
                Order::query()
                    ->with(['items'])
                    ->when($start !== null, fn (Builder $q): Builder => $q->whereBetween('created_at', [$start, $end]))
                    ->latest()
                    ->limit(8),
            )
            ->columns([
                TextColumn::make('order_number')
                    ->label(__('admin.orders.column_number'))
                    ->weight('semibold')
                    ->searchable(),
                TextColumn::make('customer_full_name')
                    ->label(__('admin.orders.column_customer'))
                    ->getStateUsing(fn (Order $record): string => $record->customerFullName()),
                TextColumn::make('total')
                    ->label(__('admin.orders.column_total'))
                    ->getStateUsing(fn (Order $record): string => format_price($record->totalAmount()))
                    ->alignEnd(),
                TextColumn::make('status')
                    ->label(__('admin.orders.column_status'))
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label())
                    ->color(fn (OrderStatus $state): string => $state->badgeColor()),
                TextColumn::make('payment_status')
                    ->label(__('admin.orders.column_payment'))
                    ->badge()
                    ->formatStateUsing(fn (PaymentStatus $state): string => $state->label())
                    ->color(fn (PaymentStatus $state): string => $state->badgeColor()),
                TextColumn::make('created_at')
                    ->label(__('admin.orders.date'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
