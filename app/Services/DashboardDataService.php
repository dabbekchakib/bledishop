<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * All SQL used by the administration dashboard lives here so that the
 * widgets stay thin and do not duplicate queries.
 */
class DashboardDataService
{
    /**
     * Base order query restricted to a period when a start is provided.
     */
    private function orders(?Carbon $start = null, ?Carbon $end = null): Builder
    {
        return Order::query()
            ->when($start !== null, fn (Builder $q): Builder => $q->whereBetween('created_at', [$start, $end]));
    }

    /**
     * Total revenue (cents) for the period, excluding cancelled orders.
     */
    public function revenue(?Carbon $start = null, ?Carbon $end = null): int
    {
        return (int) $this->orders($start, $end)
            ->where('status', '!=', OrderStatus::Cancelled->value)
            ->sum('total');
    }

    public function ordersCount(?Carbon $start = null, ?Carbon $end = null): int
    {
        return (int) $this->orders($start, $end)->count();
    }

    public function pendingOrders(?Carbon $start = null, ?Carbon $end = null): int
    {
        return (int) $this->orders($start, $end)
            ->where('status', OrderStatus::Pending->value)
            ->count();
    }

    public function completedOrders(?Carbon $start = null, ?Carbon $end = null): int
    {
        return (int) $this->orders($start, $end)
            ->where('status', OrderStatus::Delivered->value)
            ->count();
    }

    /**
     * Revenue (cents) bucketed by period for charts.
     *
     * @return array{labels: array<int, string>, data: array<int, float>}
     */
    public function revenueOverTime(Carbon $start, Carbon $end): array
    {
        return $this->bucketedSums(OrderItem::query(), $start, $end, 'order_items.line_total');
    }

    /**
     * Order count bucketed by period for charts.
     *
     * @return array{labels: array<int, string>, data: array<int, float>}
     */
    public function ordersOverTime(Carbon $start, Carbon $end): array
    {
        return $this->bucketedSums(Order::query(), $start, $end, null);
    }

    /**
     * @return array{labels: array<int, string>, data: array<int, float>}
     */
    private function bucketedSums(Builder $query, Carbon $start, Carbon $end, ?string $sumColumn): array
    {
        $days = (int) ceil($start->diffInDays($end)) + 1;

        if ($days <= 45) {
            $monthly = false;
            $weekly = false;
        } elseif ($days <= 460) {
            $monthly = false;
            $weekly = true;
        } else {
            $monthly = true;
            $weekly = false;
        }

        $isOrder = $sumColumn === null;
        $dateColumn = $isOrder ? 'created_at' : 'orders.created_at';

        $rows = $query
            ->selectRaw($isOrder ? 'COUNT(*) as value' : 'SUM('.$sumColumn.') as value')
            ->selectRaw('DATE('.$dateColumn.') as day')
            ->where($dateColumn, '>=', $start)
            ->where($dateColumn, '<=', $end);

        if (! $isOrder) {
            $rows->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.status', '!=', OrderStatus::Cancelled->value);
        }

        $daily = $rows->groupBy('day')->pluck('value', 'day');

        $buckets = [];
        $cursor = $start->copy()->startOfDay();
        $dayIndex = 0;

        while ($cursor->lte($end)) {
            $day = $cursor->format('Y-m-d');
            $value = $isOrder
                ? (float) ($daily[$day] ?? 0)
                : round(((int) ($daily[$day] ?? 0)) / 100, 2);

            if ($monthly) {
                $key = $cursor->format('Y-m');
                $label = $cursor->translatedFormat('M Y');
            } elseif ($weekly) {
                $key = (string) intdiv($dayIndex, 7);
                $label = __('admin.dashboard.week_of').' '.$cursor->copy()->subDays($dayIndex % 7)->format('d/m');
            } else {
                $key = $day;
                $label = $cursor->format('d/m');
            }

            $buckets[$key] ??= ['label' => $label, 'value' => 0.0];
            $buckets[$key]['value'] += $value;

            $cursor->addDay();
            $dayIndex++;
        }

        $labels = [];
        $data = [];

        foreach ($buckets as $bucket) {
            $labels[] = $bucket['label'];
            $data[] = $bucket['value'];
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * @return array{labels: array<int, string>, data: array<int, int>, colors: array<int, string>}
     */
    public function ordersByStatus(?Carbon $start = null, ?Carbon $end = null): array
    {
        $counts = $this->orders($start, $end)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $labels = [];
        $data = [];
        $colors = [];

        foreach (OrderStatus::cases() as $status) {
            $labels[] = $status->label();
            $data[] = (int) ($counts[$status->value] ?? 0);
            $colors[] = $this->statusColor($status);
        }

        return ['labels' => $labels, 'data' => $data, 'colors' => $colors];
    }

    private function statusColor(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::Pending => '#F59E0B',
            OrderStatus::Confirmed => '#3B82F6',
            OrderStatus::Processing => '#8B5CF6',
            OrderStatus::Shipped => '#06B6D4',
            OrderStatus::Delivered => '#16A34A',
            OrderStatus::Cancelled => '#DC2626',
            OrderStatus::OnHold => '#64748B',
        };
    }

    /**
     * Revenue per top category.
     *
     * @return array{labels: array<int, string>, data: array<int, float>, colors: array<int, string>}
     */
    public function revenueByCategory(?Carbon $start = null, ?Carbon $end = null): array
    {
        $rows = OrderItem::query()
            ->selectRaw('categories.id as category_id')
            ->selectRaw('SUM(order_items.line_total) as revenue')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('product_category', 'product_category.product_id', '=', 'order_items.product_id')
            ->join('categories', 'categories.id', '=', 'product_category.category_id')
            ->where('orders.status', '!=', OrderStatus::Cancelled->value)
            ->when($start !== null, fn (Builder $q): Builder => $q->whereBetween('orders.created_at', [$start, $end]))
            ->groupBy('categories.id')
            ->orderByDesc('revenue')
            ->limit(8)
            ->get();

        $names = $this->entityNames(Category::class, $rows->pluck('category_id'));

        return $this->percentDataset(
            $rows->pluck('revenue')->map(fn ($v): float => round(((int) $v) / 100, 2)),
            fn (int $id): string => $names[$id] ?? (string) $id,
            $rows->pluck('category_id'),
        );
    }

    /**
     * Revenue per brand.
     *
     * @return array{labels: array<int, string>, data: array<int, float>, colors: array<int, string>}
     */
    public function revenueByBrand(?Carbon $start = null, ?Carbon $end = null): array
    {
        $rows = OrderItem::query()
            ->selectRaw('products.brand_id as brand_id')
            ->selectRaw('SUM(order_items.line_total) as revenue')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('products.brand_id', '!=', null)
            ->where('orders.status', '!=', OrderStatus::Cancelled->value)
            ->when($start !== null, fn (Builder $q): Builder => $q->whereBetween('orders.created_at', [$start, $end]))
            ->groupBy('products.brand_id')
            ->orderByDesc('revenue')
            ->limit(8)
            ->get();

        $names = $this->entityNames(Brand::class, $rows->pluck('brand_id'));

        return $this->singleDataset(
            $rows->pluck('revenue')->map(fn ($v): float => round(((int) $v) / 100, 2)),
            fn (int $id): string => $names[$id] ?? (string) $id,
            $rows->pluck('brand_id'),
        );
    }

    /**
     * Top selling products by quantity.
     *
     * @return array{labels: array<int, string>, data: array<int, float>}
     */
    public function topProducts(?Carbon $start = null, ?Carbon $end = null): array
    {
        $rows = OrderItem::query()
            ->selectRaw('order_items.product_id')
            ->selectRaw('SUM(order_items.quantity) as qty')
            ->selectRaw('SUM(order_items.line_total) as revenue')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', '!=', OrderStatus::Cancelled->value)
            ->when($start !== null, fn (Builder $q): Builder => $q->whereBetween('orders.created_at', [$start, $end]))
            ->groupBy('order_items.product_id')
            ->orderByDesc('qty')
            ->limit(10)
            ->get();

        $rows = $rows->sortByDesc(fn ($row): float => (float) $row->qty)->values();

        $labels = [];
        $data = [];

        foreach ($rows as $row) {
            $labels[] = $this->productLabel((int) $row->product_id);
            $data[] = (float) $row->qty;
        }

        return ['labels' => $labels, 'data' => $data];
    }

    private function productLabel(int $productId): string
    {
        $product = Product::with(['translations'])->find($productId);

        return $product?->translatedName() ?: ('#'.$productId);
    }

    /**
     * @return array<int, string> id => translated name
     */
    private function entityNames(string $modelClass, iterable $ids): array
    {
        $ids = collect($ids)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return $modelClass::query()
            ->with('translations')
            ->whereIn('id', $ids->all())
            ->get()
            ->mapWithKeys(fn ($entity): array => [$entity->id => $entity->translatedName() ?: ('#'.$entity->id)])
            ->all();
    }

    /**
     * @param  Collection<int, float>  $values
     * @param  Collection<int, mixed>|array<int, mixed>  $keys
     * @return array{labels: array<int, string>, data: array<int, float>, colors: array<int, string>}
     */
    private function percentDataset($values, callable $label, $keys): array
    {
        $total = $values->sum();
        $palette = $this->palette();

        $labels = [];
        $data = [];
        $colors = [];

        $index = 0;

        foreach ($keys as $i => $key) {
            $labels[] = $label((int) $key);
            $share = $total > 0 ? round(($values[$i] / $total) * 100, 1) : 0.0;
            $data[] = $share;
            $colors[] = $palette[$index % count($palette)];
            $index++;
        }

        return ['labels' => $labels, 'data' => $data, 'colors' => $colors];
    }

    private function singleDataset(
        Collection $values,
        callable $label,
        Collection $keys,
    ): array {
        $palette = $this->palette();

        $labels = [];
        $data = [];
        $colors = [];

        foreach ($keys as $i => $key) {
            $labels[] = $label((int) $key);
            $data[] = $values[$i];
            $colors[] = $palette[$i % count($palette)];
        }

        return ['labels' => $labels, 'data' => $data, 'colors' => $colors];
    }

    /**
     * @return array<int, string>
     */
    private function palette(): array
    {
        return ['#F59E0B', '#3B82F6', '#10B981', '#8B5CF6', '#EC4899', '#06B6D4', '#F97316', '#14B8A6'];
    }

    public function customersTotal(): int
    {
        return (int) User::role('customer')->count();
    }

    public function newCustomers(?Carbon $start = null, ?Carbon $end = null): int
    {
        return (int) User::role('customer')
            ->when($start !== null, fn (Builder $q): Builder => $q->whereBetween('created_at', [$start, $end]))
            ->count();
    }

    public function productsTotal(): int
    {
        return (int) Product::withTrashed()->count();
    }

    public function outOfStockCount(): int
    {
        return (int) Product::query()->outOfStock()->count();
    }

    public function lowStockCount(): int
    {
        $products = Product::query()
            ->withTrashed()
            ->where('type', 'simple')
            ->where('manage_stock', true)
            ->where('low_stock_threshold', '>', 0)
            ->where('stock_quantity', '>', 0)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->count();

        $variants = ProductVariant::query()
            ->withTrashed()
            ->where('manage_stock', true)
            ->where('low_stock_threshold', '>', 0)
            ->where('stock_quantity', '>', 0)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->count();

        return $products + $variants;
    }
}
