<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardPeriod;
use App\Services\DashboardDataService;
use Filament\Widgets\BarChartWidget;

class OrdersTrendChart extends BarChartWidget
{
    use HasDashboardPeriod;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '300px';

    public static function canView(): bool
    {
        return auth()->user()?->can('orders.view') ?? false;
    }

    public function getHeading(): string
    {
        return __('admin.dashboard.orders_trend');
    }

    public function getDescription(): ?string
    {
        return $this->dashboardPeriodLabel();
    }

    protected function getData(): array
    {
        [$start, $end] = $this->dashboardChartRange();

        $series = app(DashboardDataService::class)->ordersOverTime($start, $end);

        return [
            'labels' => $series['labels'],
            'datasets' => [
                [
                    'label' => __('admin.dashboard.stat_orders'),
                    'data' => $series['data'],
                    'backgroundColor' => '#3B82F6',
                ],
            ],
        ];
    }
}
