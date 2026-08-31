<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\MarketingDashboard;
use App\Filament\Widgets\DashboardPeriodFilter;
use App\Filament\Widgets\LatestOrdersTable;
use App\Filament\Widgets\OrdersByStatusChart;
use App\Filament\Widgets\OrdersTrendChart;
use App\Filament\Widgets\RecentCustomersTable;
use App\Filament\Widgets\RevenueByBrandChart;
use App\Filament\Widgets\RevenueByCategoryChart;
use App\Filament\Widgets\RevenueChart;
use App\Filament\Widgets\RevenueStats;
use App\Filament\Widgets\StockStatusTable;
use App\Filament\Widgets\TopProductsChart;
use App\Http\Middleware\SetAdminLocale;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('BlediShop')
            ->sidebarCollapsibleOnDesktop()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->navigationGroups([
                __('admin.nav.catalogue'),
                __('admin.nav.orders'),
                __('admin.nav.marketing'),
                __('admin.nav.content'),
                __('admin.nav.configuration'),
                __('admin.nav.seo'),
                __('admin.nav.administration'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
                MarketingDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                DashboardPeriodFilter::class,
                RevenueStats::class,
                RevenueChart::class,
                OrdersTrendChart::class,
                OrdersByStatusChart::class,
                RevenueByCategoryChart::class,
                RevenueByBrandChart::class,
                TopProductsChart::class,
                LatestOrdersTable::class,
                StockStatusTable::class,
                RecentCustomersTable::class,
            ])
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn (): string => Blade::render('@livewire(\'admin-notification-bell\')').view('filament.language-switcher')->render(),
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                SetAdminLocale::class,
            ]);
    }
}
