<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class StockStatusTable extends TableWidget
{
    protected static ?int $sort = 9;

    protected int|string|array $columnSpan = ['lg' => 2];

    public static function canView(): bool
    {
        return auth()->user()?->can('products.view') ?? false;
    }

    public function getTableHeading(): string
    {
        return __('admin.dashboard.stock_alerts');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->active()
                    ->where(function (Builder $group): void {
                        $group->where(fn (Builder $q): Builder => $q->outOfStock())
                            ->orWhere(function (Builder $q): void {
                                $q->where('type', 'simple')
                                    ->where('manage_stock', true)
                                    ->where('low_stock_threshold', '>', 0)
                                    ->where('stock_quantity', '>', 0)
                                    ->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
                            });
                    })
                    ->with('translations')
                    ->orderByRaw('CASE WHEN stock_quantity > 0 THEN 0 ELSE 1 END, stock_quantity ASC')
                    ->orderByDesc('stock_quantity')
                    ->limit(12),
            )
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.dashboard.col_product'))
                    ->getStateUsing(fn (Product $record): string => $record->translatedName() ?: $record->sku)
                    ->weight('semibold')
                    ->searchable(),
                TextColumn::make('sku')
                    ->label(__('admin.dashboard.col_sku'))
                    ->color('gray'),
                TextColumn::make('type')
                    ->label(__('admin.dashboard.col_type'))
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => ucfirst((string) $state)),
                TextColumn::make('stock_quantity')
                    ->label(__('admin.dashboard.col_stock'))
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('low_stock_threshold')
                    ->label(__('admin.dashboard.col_threshold'))
                    ->numeric()
                    ->alignEnd(),
                IconColumn::make('out_of_stock')
                    ->label(__('admin.dashboard.col_out_of_stock'))
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->getStateUsing(fn (Product $record): bool => $record->stock_quantity <= 0),
            ])
            ->paginated(false);
    }
}
