<?php

namespace App\Filament\Widgets;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderDiscount;
use App\Models\Promotion;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MarketingStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = null;

    public static function canView(): bool
    {
        return auth()->user()?->can('coupons.view') ?? false;
    }

    public function getHeading(): string
    {
        return __('admin.marketing.dashboard.kpis');
    }

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $promotions = Promotion::query()->where('active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->count();

        $coupons = Coupon::query()->where('active', true)
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->count();

        $discount30d = (int) Order::query()
            ->where('created_at', '>=', now()->subDays(30)->startOfDay())
            ->sum('discount');

        $discountAll = (int) Order::query()->sum('discount');

        $redemptions = OrderDiscount::query()
            ->where('kind', 'coupon')
            ->count();

        return [
            Stat::make(__('admin.marketing.dashboard.stat_active_promotions'), $promotions)
                ->icon(Heroicon::OutlinedFire)
                ->color('danger'),

            Stat::make(__('admin.marketing.dashboard.stat_active_coupons'), $coupons)
                ->icon(Heroicon::OutlinedTicket)
                ->color('primary'),

            Stat::make(__('admin.marketing.dashboard.stat_discount_30d'), format_price($discount30d / 100))
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('success'),

            Stat::make(__('admin.marketing.dashboard.stat_discount_all'), format_price($discountAll / 100))
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('success'),

            Stat::make(__('admin.marketing.dashboard.stat_redemptions'), $redemptions)
                ->icon(Heroicon::OutlinedCheckBadge)
                ->color('warning'),
        ];
    }
}
