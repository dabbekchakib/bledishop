<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Filament\Widgets\DashboardPeriodFilter;
use App\Filament\Widgets\RevenueChart;
use App\Filament\Widgets\RevenueStats;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Concerns\InteractsWithRoles;
use Tests\TestCase;

class DashboardWidgetsTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingsSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_dashboard_page_loads_in_each_locale(): void
    {
        $user = $this->createUserWithRole(Role::SuperAdmin->value);

        foreach (['fr', 'en', 'ar'] as $locale) {
            $this->withSession(['admin_locale' => $locale])
                ->actingAs($user)
                ->get('/admin')
                ->assertSuccessful();
        }
    }

    public function test_dashboard_widgets_render_headings_in_french(): void
    {
        $user = $this->createUserWithRole(Role::SuperAdmin->value);

        Livewire::actingAs($user)
            ->test(DashboardPeriodFilter::class)
            ->assertSee(__('admin.dashboard.period_filter'));

        Livewire::actingAs($user)
            ->test(RevenueStats::class)
            ->assertSee(__('admin.dashboard.kpis'));

        Livewire::actingAs($user)
            ->test(RevenueChart::class)
            ->assertSee(__('admin.dashboard.revenue_trend'));
    }

    public function test_period_filter_renders_selected_locale(): void
    {
        $user = $this->createUserWithRole(Role::SuperAdmin->value);

        app()->setLocale('en');
        Livewire::actingAs($user)
            ->test(DashboardPeriodFilter::class)
            ->assertSee('Period filter');

        app()->setLocale('ar');
        Livewire::actingAs($user)
            ->test(DashboardPeriodFilter::class)
            ->assertSee('فلتر الفترة');
    }

    public function test_period_filter_broadcasts_period_change(): void
    {
        Livewire::test(DashboardPeriodFilter::class)
            ->set('period', 'year')
            ->assertDispatched('dashboardPeriodChanged');
    }

    public function test_chart_widget_reacts_to_shared_period_listener(): void
    {
        $user = $this->createUserWithRole(Role::SuperAdmin->value);

        Livewire::actingAs($user)
            ->test(RevenueChart::class)
            ->assertSet('period', '30d')
            ->dispatch('dashboardPeriodChanged', period: 'year')
            ->assertSet('period', 'year');
    }
}
