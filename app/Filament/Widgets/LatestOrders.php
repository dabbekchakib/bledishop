<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestOrders extends TableWidget
{
    protected static ?string $heading = 'Dernières commandes';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('orders.view') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::query()->with(['items'])->latest()->limit(6))
            ->columns([
                TextColumn::make('order_number')
                    ->label('N° commande')
                    ->weight('semibold')
                    ->searchable(),
                TextColumn::make('customer_full_name')
                    ->label('Client')
                    ->getStateUsing(fn (Order $record): string => $record->customerFullName()),
                TextColumn::make('total')
                    ->label('Total')
                    ->getStateUsing(fn (Order $record): string => format_price($record->totalAmount()))
                    ->alignEnd(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label())
                    ->color(fn (OrderStatus $state): string => $state->badgeColor()),
                TextColumn::make('payment_status')
                    ->label('Paiement')
                    ->badge()
                    ->formatStateUsing(fn (PaymentStatus $state): string => $state->label())
                    ->color(fn (PaymentStatus $state): string => $state->badgeColor()),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
