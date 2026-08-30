<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardPeriod;
use App\Services\DashboardDataService;
use Filament\Widgets\BarChartWidget;

class TopProductsChart extends BarChartWidget
{
    use HasDashboardPeriod;

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = ['lg' => 2];

    protected ?string $maxHeight = '300px';

    public static function canView(): bool
    {
        return auth()->user()?->can('orders.view') ?? false;
    }

    public function getHeading(): string
    {
        return __('admin.dashboard.top_products');
    }

    public function getDescription(): ?string
    {
        return __('admin.dashboard.top_products_quantity');
    }

    protected function getData(): array
    {
        [$start, $end] = $this->dashboardRange();

        $series = app(DashboardDataService::class)->topProducts($start, $end);

        return [
            'labels' => $series['labels'],
            'datasets' => [
                [
                    'label' => __('admin.dashboard.col_quantity'),
                    'data' => $series['data'],
                    'backgroundColor' => '#10B981',
                ],
            ],
        ];
    }
}
