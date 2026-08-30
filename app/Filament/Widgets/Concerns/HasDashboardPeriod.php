<?php

namespace App\Filament\Widgets\Concerns;

use Illuminate\Support\Carbon;
use Livewire\Attributes\On;

/**
 * Shared global period filter for the administration dashboard.
 *
 * Widgets using this trait keep their period in sync with the
 * DashboardPeriodFilter widget through a Livewire broadcast. The period
 * controls every date-bounded stat and chart on the dashboard.
 */
trait HasDashboardPeriod
{
    public string $period = '30d';

    public ?string $from = null;

    public ?string $to = null;

    /**
     * The period keys accepted by the dashboard filter.
     *
     * @return array<int, string>
     */
    public function dashboardPeriods(): array
    {
        return ['today', '7d', '30d', 'month', 'year', '12m', 'all', 'custom'];
    }

    #[On('dashboardPeriodChanged')]
    public function setDashboardPeriod(?string $period = null, ?string $from = null, ?string $to = null): void
    {
        if ($period !== null) {
            $this->period = in_array($period, $this->dashboardPeriods(), true) ? $period : '30d';
        }

        $this->from = filled($from) ? $from : null;
        $this->to = filled($to) ? $to : null;
    }

    /**
     * The [start, end] Carbon range for the selected period. start is null
     * when the period covers all time.
     *
     * @return array{Carbon|null, Carbon}
     */
    public function dashboardRange(): array
    {
        if ($this->period === 'custom' && filled($this->from)) {
            return [
                Carbon::parse($this->from)->startOfDay(),
                Carbon::parse($this->to ?? $this->from)->endOfDay(),
            ];
        }

        return match ($this->period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            '7d' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
            '12m' => [now()->subMonths(11)->startOfMonth(), now()->endOfDay()],
            default => [null, now()->endOfDay()],
        };
    }

    /**
     * The period immediately preceding the selected one, used to compute
     * period-over-period evolution. Returns null when no comparison makes
     * sense (all time).
     *
     * @return array{Carbon|null, Carbon|null}
     */
    public function previousDashboardRange(): array
    {
        if ($this->period === 'custom' && filled($this->from)) {
            $start = Carbon::parse($this->from)->startOfDay();
            $end = Carbon::parse($this->to ?? $this->from)->endOfDay();
            $length = $start->diffInSeconds($end) + 1;

            return [$start->copy()->subSeconds($length), $start->copy()->subSecond()];
        }

        return match ($this->period) {
            'today' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            '7d' => [now()->subDays(13)->startOfDay(), now()->subDays(7)->endOfDay()],
            'month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            'year' => [now()->subYearNoOverflow()->startOfYear(), now()->subYearNoOverflow()->endOfYear()],
            '12m' => [now()->subMonths(23)->startOfMonth(), now()->subMonths(12)->endOfDay()],
            default => [null, null],
        };
    }

    /**
     * Bounded [start, end] range used by time-series charts. Unbounded
     * periods (all time) fall back to a sensible recent window.
     *
     * @return array{Carbon, Carbon}
     */
    public function dashboardChartRange(): array
    {
        [$start, $end] = $this->dashboardRange();

        if ($start === null) {
            $start = $this->period === '12m'
                ? now()->subMonths(11)->startOfMonth()
                : now()->subDays(29)->startOfDay();
        }

        return [$start, $end ?? now()->endOfDay()];
    }

    /**
     * Period-over-period growth in percent (0 when unavailable).
     */
    public function revenueGrowth(?int $current, ?int $previous): float
    {
        if ($previous === null || $previous <= 0) {
            return 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Translatable label of the selected period.
     */
    public function dashboardPeriodLabel(): string
    {
        return match ($this->period) {
            'today' => __('admin.dashboard.period_today'),
            '7d' => __('admin.dashboard.period_7d'),
            '30d' => __('admin.dashboard.period_30d'),
            'month' => __('admin.dashboard.period_month'),
            'year' => __('admin.dashboard.period_year'),
            '12m' => __('admin.dashboard.period_12m'),
            'all' => __('admin.dashboard.period_all'),
            'custom' => __('admin.dashboard.period_custom'),
            default => __('admin.dashboard.period_30d'),
        };
    }
}
