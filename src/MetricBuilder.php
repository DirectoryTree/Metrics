<?php

namespace DirectoryTree\Metrics;

use Illuminate\Database\Eloquent\Builder;

class MetricBuilder extends Builder
{
    public function thisMonth(Builder $query): Builder
    {
        return $query
            ->where('year', now()->year)
            ->where('month', now()->month);
    }

    public function lastMonth(Builder $query): Builder
    {
        $now = now()->subMonth();

        return $query
            ->where('year', $now->year)
            ->where('month', $now->month);
    }
}