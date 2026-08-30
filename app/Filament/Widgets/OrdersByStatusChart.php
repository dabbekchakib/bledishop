<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardPeriod;
use App\Services\DashboardDataService;
use Filament\Widgets\DoughnutChartWidget;

class OrdersByStatusChart extends DoughnutChartWidget
{
    use HasDashboardPeriod;

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '300px';

    public static function canView(): bool
    {
        return auth()->user()?->can('orders.view') ?? false;
    }

    public function getHeading(): string
    {
        return __('admin.dashboard.orders_by_status');
    }

    public function getDescription(): ?string
    {
        return $this->dashboardPeriodLabel();
    }

    protected function getData(): array
    {
        [$start, $end] = $this->dashboardRange();

        $series = app(DashboardDataService::class)->ordersByStatus($start, $end);

        return [
            'labels' => $series['labels'],
            'datasets' => [
                [
                    'data' => $series['data'],
                    'backgroundColor' => $series['colors'],
                ],
            ],
        ];
    }
}
