<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\MarketingStats;
use App\Filament\Widgets\RecentOrderDiscountsTable;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class MarketingDashboard extends Page
{
    protected string $view = 'filament.pages.marketing-dashboard';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?string $slug = 'marketing';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('admin.nav.marketing');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.marketing.dashboard.title');
    }

    public function getTitle(): string
    {
        return __('admin.marketing.dashboard.title');
    }

    public static function canAccess(): bool
    {
        return (bool) (auth()->user()?->can('coupons.view') ?? false);
    }

    /**
     * @return array<int, class-string>
     */
    public function getWidgets(): array
    {
        return [
            MarketingStats::class,
            RecentOrderDiscountsTable::class,
        ];
    }
}
