<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class DashboardPeriodFilter extends Widget
{
    protected static ?int $sort = 0;

    protected string $view = 'filament.widgets.dashboard-period-filter';

    protected int|string|array $columnSpan = 'full';

    public string $period = '30d';

    public ?string $from = null;

    public ?string $to = null;

    public function updatedPeriod(): void
    {
        $this->broadcast();
    }

    public function updatedFrom(): void
    {
        $this->broadcast();
    }

    public function updatedTo(): void
    {
        $this->broadcast();
    }

    protected function broadcast(): void
    {
        $this->dispatch('dashboardPeriodChanged', period: $this->period, from: $this->from, to: $this->to);
    }

    protected function getViewData(): array
    {
        $periods = [
            'today' => __('admin.dashboard.period_today'),
            '7d' => __('admin.dashboard.period_7d'),
            '30d' => __('admin.dashboard.period_30d'),
            'month' => __('admin.dashboard.period_month'),
            'year' => __('admin.dashboard.period_year'),
            '12m' => __('admin.dashboard.period_12m'),
            'all' => __('admin.dashboard.period_all'),
            'custom' => __('admin.dashboard.period_custom'),
        ];

        return [
            'presets' => $periods,
            'label' => $periods[$this->period] ?? $periods['30d'],
        ];
    }
}
