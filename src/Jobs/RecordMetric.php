<?php

namespace DirectoryTree\Metrics;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecordMetric implements ShouldQueue
{
    use Queueable;

    /**
     * Constructor.
     */
    public function __construct(
        public Measurable $metric,
    ) {}

    /**
     * Record the metric.
     */
    public function handle(): Metric
    {
        $metric = Metric::query()->firstOrCreate([
            'name' => $this->metric->name(),
            'day' => $this->metric->day(),
            'month' => $this->metric->month(),
            'year' => $this->metric->year(),
            'measurable_type' => $this->metric->measurable()?->getMorphClass(),
            'measurable_id' => $this->metric->measurable()?->getKey(),
        ], ['value' => 0]);

        $metric->fill([
            'metadata' => [
                ...$metric->metadata,
                ...$this->metric->metadata(),
            ],
        ])->increment('value', $this->metric->value());

        return $metric;
    }
}
