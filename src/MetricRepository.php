<?php

namespace DirectoryTree\Metrics;

class MetricRepository
{
    /**
     * The metrics awaiting to be committed.
     *
     * @var Measurable[]
     */
    protected array $metrics = [];

    /**
     * Add a metric to be committed.
     */
    public function add(Measurable $metric): void
    {
        $this->metrics[] = $metric;
    }

    /**
     * Get all metrics.
     *
     * @return Measurable[]
     */
    public function all(): array
    {
        return $this->metrics;
    }

    /**
     * Flush all metrics.
     */
    public function flush(): void
    {
        $this->metrics = [];
    }
}
