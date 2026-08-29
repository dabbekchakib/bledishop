<?php

namespace App\Filament\Resources\OrdersResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitle(fn ($record): string => (string) $record->product_name)
            ->columns([
                TextColumn::make('product_name')
                    ->label('Produit'),
                TextColumn::make('variant_name')
                    ->label('Variante')
                    ->placeholder('-'),
                TextColumn::make('sku')
                    ->label('Réf.'),
                TextColumn::make('quantity')
                    ->label('Qté')
                    ->alignCenter(),
                TextColumn::make('unit_price')
                    ->label('Prix unitaire')
                    ->formatStateUsing(fn ($record): string => format_price($record->unitPriceAmount()))
                    ->alignEnd(),
                TextColumn::make('line_total')
                    ->label('Total ligne')
                    ->formatStateUsing(fn ($record): string => format_price($record->lineTotalAmount()))
                    ->alignEnd()
                    ->weight('semibold'),
            ])
            ->paginated(false);
    }
}
