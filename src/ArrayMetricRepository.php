<?php

namespace DirectoryTree\Metrics;

use Carbon\CarbonImmutable;

class ArrayMetricRepository implements MetricRepository
{
    /**
     * The metrics awaiting to be committed.
     *
     * @var array<string, Measurable>
     */
    protected array $metrics = [];

    /**
     * Constructor.
     */
    public function __construct(
        protected MeasurableEncoder $encoder,
    ) {}

    /**
     * Add a metric to be committed.
     */
    public function add(Measurable $metric): void
    {
        $key = $this->encoder->encode($metric);

        if (isset($this->metrics[$key])) {
            $existing = $this->metrics[$key];

            $this->metrics[$key] = new MetricData(
                $metric->name(),
                $metric->category(),
                $existing->value() + $metric->value(),
                CarbonImmutable::create(
                    $existing->year(),
                    $existing->month(),
                    $existing->day(),
                    $existing->hour() ?? 0
                ),
                $metric->measurable(),
                $metric->additional(),
                $existing->hour() !== null,
                $metric->databaseModel(),
            );
        } else {
            $this->metrics[$key] = $metric;
        }
    }

    /**
     * Get all metrics.
     *
     * @return Measurable[]
     */
    public function all(): array
    {
        return array_values($this->metrics);
    }

    /**
     * Flush all metrics.
     */
    public function flush(): void
    {
        $this->metrics = [];
    }
}
