<?php

namespace App\Filament\Widgets;

use App\Models\OrderDiscount;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentOrderDiscountsTable extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('coupons.view') ?? false;
    }

    public function getTableHeading(): string
    {
        return __('admin.marketing.dashboard.recent_discounts');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                OrderDiscount::query()
                    ->with(['order'])
                    ->latest()
                    ->limit(10),
            )
            ->columns([
                TextColumn::make('order.order_number')
                    ->label(__('admin.marketing.dashboard.column_order'))
                    ->weight('semibold')
                    ->searchable(),
                TextColumn::make('name')
                    ->label(__('admin.marketing.dashboard.column_discount'))
                    ->getStateUsing(fn (OrderDiscount $record): string => $record->name ?: $record->code),
                TextColumn::make('kind')
                    ->label(__('admin.marketing.dashboard.column_kind'))
                    ->badge(),
                TextColumn::make('amount')
                    ->label(__('admin.marketing.dashboard.column_amount'))
                    ->getStateUsing(fn (OrderDiscount $record): string => format_price($record->amount / 100))
                    ->alignEnd(),
                TextColumn::make('created_at')
                    ->label(__('admin.marketing.dashboard.column_date'))
                    ->dateTime('d/m/Y H:i'),
            ])
            ->paginated(false);
    }
}
