<?php

namespace DirectoryTree\Metrics;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class MetricBuilder extends Builder
{
    /**
     * Get metrics for today.
     */
    public function today(): self
    {
        return $this->onDate(today());
    }

    /**
     * Get metrics for yesterday.
     */
    public function yesterday(): self
    {
        return $this->onDate(
            today()->subDay()
        );
    }

    /**
     * Get metrics for this week.
     */
    public function thisWeek(): self
    {
        return $this->betweenDates(
            today()->startOfWeek(),
            today()->endOfWeek(),
        );
    }

    /**
     * Get metrics for last week.
     */
    public function lastWeek(): self
    {
        return $this->betweenDates(
            today()->subWeek()->startOfWeek(),
            today()->subWeek()->endOfWeek(),
        );
    }

    /**
     * Get metrics for this month.
     */
    public function thisMonth(): self
    {
        return $this->betweenDates(
            today()->startOfMonth(),
            today()->endOfMonth(),
        );
    }

    /**
     * Get metrics for last month.
     */
    public function lastMonth(): self
    {
        return $this->betweenDates(
            today()->subMonth()->startOfMonth(),
            today()->subMonth()->endOfMonth(),
        );
    }

    /**
     * Get metrics for last month without overflow.
     */
    public function lastMonthNoOverflow(): self
    {
        return $this->betweenDates(
            today()->subMonthNoOverflow()->startOfMonth(),
            today()->subMonthNoOverflow()->endOfMonth(),
        );
    }

    /**
     * Get metrics for this quarter.
     */
    public function thisQuarter(): self
    {
        return $this->betweenDates(
            today()->startOfQuarter(),
            today()->endOfQuarter(),
        );
    }

    /**
     * Get metrics for last quarter.
     */
    public function lastQuarter(): self
    {
        return $this->betweenDates(
            today()->subQuarter()->startOfQuarter(),
            today()->subQuarter()->endOfQuarter(),
        );
    }

    /**
     * Get metrics for last quarter without overflow.
     */
    public function lastQuarterNoOverflow(): self
    {
        return $this->betweenDates(
            today()->subQuarterNoOverflow()->startOfQuarter(),
            today()->subQuarterNoOverflow()->endOfQuarter(),
        );
    }

    /**
     * Get metrics for this year.
     */
    public function thisYear(): self
    {
        return $this->betweenDates(
            today()->startOfYear(),
            today()->endOfYear(),
        );
    }

    /**
     * Get metrics for last year.
     */
    public function lastYear(): self
    {
        return $this->betweenDates(
            today()->subYear()->startOfYear(),
            today()->subYear()->endOfYear(),
        );
    }

    /**
     * Get metrics for last year without overflow.
     */
    public function lastYearNoOverflow(): self
    {
        return $this->betweenDates(
            today()->subYearNoOverflow()->startOfYear(),
            today()->subYearNoOverflow()->endOfYear(),
        );
    }

    /**
     * Get metrics between two dates.
     */
    public function betweenDates(CarbonInterface $start, CarbonInterface $end): self
    {
        return $this->whereRaw(
            '(year, month, day) BETWEEN (?, ?, ?) AND (?, ?, ?)',
            [
                $start->year, $start->month, $start->day,
                $end->year,   $end->month,   $end->day,
            ]
        );
    }

    /**
     * Get metrics on a specific date.
     */
    public function onDate(CarbonInterface $date): self
    {
        return $this->where(function (Builder $query) use ($date) {
            $query
                ->where('year', $date->year)
                ->where('month', $date->month)
                ->where('day', $date->day);
        });
    }
}
