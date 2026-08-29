<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Enums\OrderStatus;
use Filament\Resources\RelationManagers\RelationManager;
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
                    ->weight('semibold'),
                TextColumn::make('total')
                    ->label('Total')
                    ->formatStateUsing(fn ($record): string => format_price($record->totalAmount()))
                    ->alignEnd(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label())
                    ->color(fn (OrderStatus $state): string => $state->badgeColor()),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->date('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
