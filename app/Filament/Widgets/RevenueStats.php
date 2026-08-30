<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardPeriod;
use App\Services\DashboardDataService;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RevenueStats extends StatsOverviewWidget
{
    use HasDashboardPeriod;

    protected static ?int $sort = 1;

    protected ?string $heading = null;

    private ?DashboardDataService $dashboardData = null;

    public static function canView(): bool
    {
        return auth()->user()?->can('orders.view') ?? false;
    }

    public function getHeading(): string
    {
        return __('admin.dashboard.kpis');
    }

    public function getDescription(): ?string
    {
        return __('admin.dashboard.period_label').' · '.$this->dashboardPeriodLabel();
    }

    protected function getStats(): array
    {
        $service = $this->dataService();

        [$start, $end] = $this->dashboardRange();
        [$prevStart, $prevEnd] = $this->previousDashboardRange();

        $revenue = $service->revenue($start, $end);
        $prevRevenue = $service->revenue($prevStart, $prevEnd);
        $growth = $this->revenueGrowth($revenue, $prevRevenue);

        $revenueToday = $service->revenue(now()->startOfDay(), now()->endOfDay());
        $revenueMonth = $service->revenue(now()->startOfMonth(), now()->endOfMonth());
        $revenueYear = $service->revenue(now()->startOfYear(), now()->endOfYear());

        $orders = $service->ordersCount($start, $end);
        $pending = $service->pendingOrders($start, $end);
        $completed = $service->completedOrders($start, $end);

        $sparkStart = $start ?? now()->subDays(29)->startOfDay();
        $revenueSpark = $service->revenueOverTime($sparkStart, $end ?? now());
        $ordersSpark = $service->ordersOverTime($sparkStart, $end ?? now());

        $growthIcon = $growth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
        $growthColor = $growth >= 0 ? 'success' : 'danger';

        return [
            Stat::make(__('admin.dashboard.stat_revenue'), format_price($revenue / 100))
                ->description($this->growthDescription($growth))
                ->descriptionIcon($growthIcon)
                ->color($growthColor)
                ->icon(Heroicon::OutlinedBanknotes)
                ->chart($this->sparkline($revenueSpark['data']))
                ->chartColor('#F59E0B'),

            Stat::make(__('admin.dashboard.stat_revenue_today'), format_price($revenueToday / 100))
                ->icon(Heroicon::OutlinedCurrencyDollar)
                ->color('primary'),

            Stat::make(__('admin.dashboard.stat_revenue_month'), format_price($revenueMonth / 100))
                ->icon(Heroicon::OutlinedCurrencyDollar)
                ->color('primary'),

            Stat::make(__('admin.dashboard.stat_revenue_year'), format_price($revenueYear / 100))
                ->icon(Heroicon::OutlinedCurrencyDollar)
                ->color('primary'),

            Stat::make(__('admin.dashboard.stat_orders'), $orders)
                ->description($this->dashboardPeriodLabel())
                ->icon(Heroicon::OutlinedShoppingCart)
                ->color('info')
                ->chart($this->sparkline($ordersSpark['data']))
                ->chartColor('#3B82F6'),

            Stat::make(__('admin.dashboard.stat_pending'), $pending)
                ->description($this->dashboardPeriodLabel())
                ->icon(Heroicon::OutlinedClock)
                ->color('warning'),

            Stat::make(__('admin.dashboard.stat_completed'), $completed)
                ->description($this->dashboardPeriodLabel())
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success'),

            Stat::make(__('admin.dashboard.stat_customers'), $service->customersTotal())
                ->icon(Heroicon::OutlinedUsers)
                ->color('primary'),

            Stat::make(__('admin.dashboard.stat_new_customers'), $service->newCustomers($start, $end))
                ->description($this->dashboardPeriodLabel())
                ->icon(Heroicon::OutlinedUserPlus)
                ->color('info'),

            Stat::make(__('admin.dashboard.stat_products'), $service->productsTotal())
                ->icon(Heroicon::OutlinedCube)
                ->color('primary'),

            Stat::make(__('admin.dashboard.stat_out_of_stock'), $service->outOfStockCount())
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color('danger'),

            Stat::make(__('admin.dashboard.stat_low_stock'), $service->lowStockCount())
                ->icon(Heroicon::OutlinedExclamationCircle)
                ->color('warning'),
        ];
    }

    private function dataService(): DashboardDataService
    {
        return $this->dashboardData ??= app(DashboardDataService::class);
    }

    private function growthDescription(float $growth): string
    {
        $label = $this->dashboardPeriodLabel();

        return __('admin.dashboard.evolution', ['percent' => number_format(abs($growth), 1, ',', ' ')]).' '.$label;
    }

    /**
     * @param  array<int, float>  $data
     * @return array<int, int|float>
     */
    private function sparkline(array $data): array
    {
        $data = array_slice($data, -30);

        return $data === [] ? [0] : $data;
    }
}
