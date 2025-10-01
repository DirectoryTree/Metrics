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
        $metrics = Collection::make(
            $this->metrics instanceof Collection
                ? $this->metrics
                : [$this->metrics]
        );

        /** @var Measurable $metric */
        if (! $metric = $metrics->first()) {
            return null;
        }

        $value = $metrics->sum(
            fn (Measurable $metric) => $metric->value()
        );

        $model = Metric::query()->firstOrCreate([
            'name' => $metric->name(),
            'day' => $metric->day(),
            'month' => $metric->month(),
            'year' => $metric->year(),
            'measurable_type' => $metric->measurable()?->getMorphClass(),
            'measurable_id' => $metric->measurable()?->getKey(),
        ], ['value' => 0]);

        $model->fill([
            'metadata' => [
                ...($model->metadata ?? []),
                ...$metric->metadata(),
            ],
        ])->increment('value', $value);

        return $model;
    }
}
