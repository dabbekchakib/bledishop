<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardPeriod;
use App\Services\DashboardDataService;
use Filament\Widgets\BarChartWidget;

class RevenueByBrandChart extends BarChartWidget
{
    use HasDashboardPeriod;

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '300px';

    public static function canView(): bool
    {
        return auth()->user()?->can('orders.view') ?? false;
    }

    public function getHeading(): string
    {
        return __('admin.dashboard.revenue_by_brand');
    }

    public function getDescription(): ?string
    {
        return $this->dashboardPeriodLabel();
    }

    protected function getData(): array
    {
        [$start, $end] = $this->dashboardRange();

        $series = app(DashboardDataService::class)->revenueByBrand($start, $end);

        return [
            'labels' => $series['labels'],
            'datasets' => [
                [
                    'label' => __('admin.dashboard.revenue'),
                    'data' => $series['data'],
                    'backgroundColor' => $series['colors'],
                ],
            ],
        ];
    }
}
