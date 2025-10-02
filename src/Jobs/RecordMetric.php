<?php

namespace DirectoryTree\Metrics\Jobs;

use DirectoryTree\Metrics\Measurable;
use DirectoryTree\Metrics\Metric;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;

class RecordMetric implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    /**
     * Constructor.
     */
    public function __construct(
        public Collection|Measurable $metrics,
    ) {}

    /**
     * Record the metric.
     */
    public function handle(): ?Metric
    {
        $metrics = Collection::wrap($this->metrics);

        /** @var Measurable $metric */
        if (! $metric = $metrics->first()) {
            return null;
        }

        $value = $metrics->sum(
            fn (Measurable $metric) => $metric->value()
        );

        $model = Metric::query()->firstOrCreate([
            'name' => $metric->name(),
            'category' => $metric->category(),
            'year' => $metric->year(),
            'month' => $metric->month(),
            'day' => $metric->day(),
            'measurable_type' => $metric->measurable()?->getMorphClass(),
            'measurable_id' => $metric->measurable()?->getKey(),
        ], ['value' => 0]);

        $model->increment('value', $value);

        return $model;
    }
}
