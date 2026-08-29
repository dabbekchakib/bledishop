<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Enums\OrderStatus;
use App\Filament\Resources\OrdersResource;
use App\Models\Order;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitle(fn ($record): string => (string) $record->order_number)
            ->columns([
                TextColumn::make('order_number')
                    ->label('N° commande')
                    ->searchable()
                    ->weight('semibold'),
                TextColumn::make('items_count')
                    ->label('Articles')
                    ->counts('items')
                    ->alignCenter(),
                TextColumn::make('total')
                    ->label('Total')
                    ->formatStateUsing(fn ($record): string => format_price($record->totalAmount()))
                    ->sortable(query: fn ($query, string $direction) => $query->orderBy('total', $direction))
                    ->alignEnd(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label())
                    ->color(fn (OrderStatus $state): string => $state->badgeColor()),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->date('d/m/Y H:i')
                    ->sortable()
                    ->description(fn (Order $record): ?string => $record->payment_status?->label()),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Order $record): string => OrdersResource::getUrl('view', ['record' => $record]))
                    ->visible(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
