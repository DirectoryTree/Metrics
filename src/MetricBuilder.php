<?php

namespace DirectoryTree\Metrics;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TMetric of Metric
 *
 * @extends Builder<TMetric>
 */
class MetricBuilder extends Builder
{
    /**
     * Get metrics for today.
     *
     * @return $this
     */
    public function today(): static
    {
        return $this->onDate(today());
    }

    /**
     * Get metrics for yesterday.
     *
     * @return $this
     */
    public function yesterday(): static
    {
        return $this->onDate(
            today()->subDay()
        );
    }

    /**
     * Get metrics for this hour.
     *
     * @return $this
     */
    public function thisHour(): static
    {
        return $this->onDateTime(now());
    }

    /**
     * Get metrics for last hour.
     *
     * @return $this
     */
    public function lastHour(): static
    {
        return $this->onDateTime(now()->subHour());
    }

    /**
     * Get metrics for this week.
     *
     * @return $this
     */
    public function thisWeek(): static
    {
        return $this->betweenDates(
            today()->startOfWeek(),
            today()->endOfWeek(),
        );
    }

    /**
     * Get metrics for last week.
     *
     * @return $this
     */
    public function lastWeek(): static
    {
        return $this->betweenDates(
            today()->subWeek()->startOfWeek(),
            today()->subWeek()->endOfWeek(),
        );
    }

    /**
     * Get metrics for this month.
     *
     * @return $this
     */
    public function thisMonth(): static
    {
        return $this->betweenDates(
            today()->startOfMonth(),
            today()->endOfMonth(),
        );
    }

    /**
     * Get metrics for last month.
     *
     * @return $this
     */
    public function lastMonth(): static
    {
        return $this->betweenDates(
            today()->subMonth()->startOfMonth(),
            today()->subMonth()->endOfMonth(),
        );
    }

    /**
     * Get metrics for last month without overflow.
     *
     * @return $this
     */
    public function lastMonthNoOverflow(): static
    {
        return $this->betweenDates(
            today()->subMonthNoOverflow()->startOfMonth(),
            today()->subMonthNoOverflow()->endOfMonth(),
        );
    }

    /**
     * Get metrics for this quarter.
     *
     * @return $this
     */
    public function thisQuarter(): static
    {
        return $this->betweenDates(
            today()->startOfQuarter(),
            today()->endOfQuarter(),
        );
    }

    /**
     * Get metrics for last quarter.
     *
     * @return $this
     */
    public function lastQuarter(): static
    {
        return $this->betweenDates(
            today()->subQuarter()->startOfQuarter(),
            today()->subQuarter()->endOfQuarter(),
        );
    }

    /**
     * Get metrics for last quarter without overflow.
     *
     * @return $this
     */
    public function lastQuarterNoOverflow(): static
    {
        return $this->betweenDates(
            today()->subQuarterNoOverflow()->startOfQuarter(),
            today()->subQuarterNoOverflow()->endOfQuarter(),
        );
    }

    /**
     * Get metrics for this year.
     *
     * @return $this
     */
    public function thisYear(): static
    {
        return $this->betweenDates(
            today()->startOfYear(),
            today()->endOfYear(),
        );
    }

    /**
     * Get metrics for last year.
     *
     * @return $this
     */
    public function lastYear(): static
    {
        return $this->betweenDates(
            today()->subYear()->startOfYear(),
            today()->subYear()->endOfYear(),
        );
    }

    /**
     * Get metrics for last year without overflow.
     *
     * @return $this
     */
    public function lastYearNoOverflow(): static
    {
        return $this->betweenDates(
            today()->subYearNoOverflow()->startOfYear(),
            today()->subYearNoOverflow()->endOfYear(),
        );
    }

    /**
     * Get metrics between two dates.
     *
     * @return $this
     */
    public function betweenDates(CarbonInterface $start, CarbonInterface $end): static
    {
        return $this->whereRaw(
            '(year, month, day) >= (?, ?, ?) AND (year, month, day) <= (?, ?, ?)',
            [
                $start->year, $start->month, $start->day,
                $end->year,   $end->month,   $end->day,
            ]
        );
    }

    /**
     * Get metrics between two datetimes (including hours).
     *
     * @return $this
     */
    public function betweenDateTimes(CarbonInterface $start, CarbonInterface $end): static
    {
        return $this->whereRaw(
            '(year, month, day, hour) >= (?, ?, ?, ?) AND (year, month, day, hour) <= (?, ?, ?, ?)',
            [
                $start->year, $start->month, $start->day, $start->hour,
                $end->year,   $end->month,   $end->day,   $end->hour,
            ]
        );
    }

    /**
     * Get metrics on a specific date.
     *
     * @return $this
     */
    public function onDate(CarbonInterface $date): static
    {
        return $this->where(function (Builder $query) use ($date) {
            $query
                ->where('year', $date->year)
                ->where('month', $date->month)
                ->where('day', $date->day);
        });
    }

    /**
     * Get metrics on a specific date and hour.
     *
     * @return $this
     */
    public function onDateTime(CarbonInterface $hour): static
    {
        return $this->onDate($hour)->where('hour', $hour->hour);
    }
}
