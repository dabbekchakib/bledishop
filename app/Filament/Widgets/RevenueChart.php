<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardPeriod;
use App\Services\DashboardDataService;
use Filament\Widgets\LineChartWidget;

class RevenueChart extends LineChartWidget
{
    use HasDashboardPeriod;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = ['lg' => 2];

    protected ?string $maxHeight = '300px';

    public static function canView(): bool
    {
        return auth()->user()?->can('orders.view') ?? false;
    }

    public function getHeading(): string
    {
        return __('admin.dashboard.revenue_trend');
    }

    public function getDescription(): ?string
    {
        return $this->dashboardPeriodLabel();
    }

    protected function getData(): array
    {
        [$start, $end] = $this->dashboardChartRange();

        $series = app(DashboardDataService::class)->revenueOverTime($start, $end);

        return [
            'labels' => $series['labels'],
            'datasets' => [
                [
                    'label' => __('admin.dashboard.revenue'),
                    'data' => $series['data'],
                    'borderColor' => '#F59E0B',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
        ];
    }
}
