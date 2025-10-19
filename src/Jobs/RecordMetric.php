<?php

namespace DirectoryTree\Metrics\Jobs;

use DirectoryTree\Metrics\DatabaseMetricManager;
use DirectoryTree\Metrics\Measurable;
use DirectoryTree\Metrics\Metric;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Collection;

class RecordMetric implements ShouldQueue
{
    use Dispatchable, Queueable;

    /**
     * Constructor.
     */
    public function __construct(
        /** @var Collection<Measurable>|Measurable */
        public Collection|Measurable $metrics
    ) {}

    /**
     * Record the metric.
     */
    public function handle(): void
    {
        $metrics = Collection::wrap($this->metrics);

        /** @var Measurable $metric */
        if (! $metric = $metrics->first()) {
            return;
        }

        $value = $metrics->sum(
            fn (Measurable $metric) => $metric->value()
        );

        /** @var \Illuminate\Database\Eloquent\Model $model */
        $model = new DatabaseMetricManager::$model;

        $model->getConnection()->transaction(
            function (ConnectionInterface $connection) use ($metric, $value, $model) {
                $instance = $model->newQuery()->firstOrCreate([
                    ...$metric->additional(),
                    'name' => $metric->name(),
                    'category' => $metric->category(),
                    'year' => $metric->year(),
                    'month' => $metric->month(),
                    'day' => $metric->day(),
                    'hour' => $metric->hour(),
                    'measurable_type' => $metric->measurable()?->getMorphClass(),
                    'measurable_id' => $metric->measurable()?->getKey(),
                ], ['value' => 0]);

                $model->newQuery()
                    ->whereKey($instance)
                    ->increment('value', $value);
            }
        );
    }
}
