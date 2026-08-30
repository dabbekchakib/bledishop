<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardPeriod;
use App\Services\DashboardDataService;
use Filament\Widgets\BarChartWidget;

class RevenueByCategoryChart extends BarChartWidget
{
    use HasDashboardPeriod;

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '300px';

    public static function canView(): bool
    {
        return auth()->user()?->can('orders.view') ?? false;
    }

    public function getHeading(): string
    {
        return __('admin.dashboard.revenue_by_category');
    }

    public function getDescription(): ?string
    {
        return __('admin.dashboard.share_of_revenue');
    }

    protected function getData(): array
    {
        [$start, $end] = $this->dashboardRange();

        $series = app(DashboardDataService::class)->revenueByCategory($start, $end);

        return [
            'labels' => $series['labels'],
            'datasets' => [
                [
                    'label' => __('admin.dashboard.revenue_share_percent'),
                    'data' => $series['data'],
                    'backgroundColor' => $series['colors'],
                ],
            ],
        ];
    }
}
