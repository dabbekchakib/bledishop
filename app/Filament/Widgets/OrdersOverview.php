<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\StockService;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class OrdersOverview extends Widget
{
    protected string $view = 'filament.widgets.orders-overview';

    protected int|string|array $columnSpan = 'full';

    public string $period = 'month';

    public static function canView(): bool
    {
        return auth()->user()?->can('orders.view') ?? false;
    }

    public function setPeriod(string $period): void
    {
        $this->period = in_array($period, ['today', 'week', 'month', 'year', 'all'], true)
            ? $period
            : 'month';
    }

    protected function getViewData(): array
    {
        return [
            'period' => $this->period,
            'stats' => $this->stats(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function stats(): array
    {
        $query = Order::query()->when($this->periodRange() !== null, function (Builder $q): Builder {
            return $q->where('created_at', '>=', $this->periodRange());
        });

        $orders = (clone $query)->count();
        $revenue = (clone $query)
            ->where('status', '!=', OrderStatus::Cancelled->value)
            ->sum('total');
        $pending = (clone $query)
            ->where('status', OrderStatus::Pending->value)
            ->count();

        $stock = app(StockService::class);
        $lowStock = $this->lowStockCount($stock);

        return [
            'orders' => $orders,
            'revenue' => format_price($revenue / 100),
            'pending' => $pending,
            'low_stock' => $lowStock,
            'period_label' => $this->periodLabel(),
        ];
    }

    private function periodRange(): ?Carbon
    {
        return match ($this->period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => null,
        };
    }

    private function periodLabel(): string
    {
        return match ($this->period) {
            'today' => 'Aujourd\'hui',
            'week' => 'Cette semaine',
            'month' => 'Ce mois',
            'year' => 'Cette année',
            default => 'Tout',
        };
    }

    private function lowStockCount(StockService $stock): int
    {
        $count = 0;

        Product::query()
            ->where('type', 'simple')
            ->where('manage_stock', true)
            ->get()
            ->each(function (Product $product) use ($stock, &$count): void {
                if ($stock->isLowStock($product)) {
                    $count++;
                }
            });

        ProductVariant::query()
            ->where('manage_stock', true)
            ->get()
            ->each(function (ProductVariant $variant) use ($stock, &$count): void {
                if ($stock->isLowStock($variant)) {
                    $count++;
                }
            });

        return $count;
    }
}
