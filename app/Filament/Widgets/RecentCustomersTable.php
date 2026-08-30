<?php

namespace App\Filament\Widgets;

use App\Enums\Role;
use App\Filament\Widgets\Concerns\HasDashboardPeriod;
use App\Models\User;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentCustomersTable extends TableWidget
{
    use HasDashboardPeriod;

    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return auth()->user()?->can('customers.view') ?? false;
    }

    public function getTableHeading(): string
    {
        return __('admin.dashboard.recent_customers');
    }

    public function table(Table $table): Table
    {
        [$start, $end] = $this->dashboardRange();

        return $table
            ->query(
                User::query()
                    ->role(Role::Customer->value)
                    ->when($start !== null, fn (Builder $q): Builder => $q->whereBetween('created_at', [$start, $end]))
                    ->withCount('orders')
                    ->latest()
                    ->limit(8),
            )
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.customers.column_name'))
                    ->weight('semibold')
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('admin.customers.column_email'))
                    ->color('gray')
                    ->searchable(),
                TextColumn::make('orders_count')
                    ->label(__('admin.dashboard.col_orders'))
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('created_at')
                    ->label(__('admin.dashboard.col_joined'))
                    ->dateTime('d/m/Y'),
                IconColumn::make('is_active')
                    ->label(__('admin.customers.column_status'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
            ])
            ->paginated(false);
    }
}
