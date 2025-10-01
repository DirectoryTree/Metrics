<?php

namespace DirectoryTree\Metrics;

interface MetricRepository
{
    /**
     * Add a metric to be committed.
     */
    public function add(Measurable $metric): void;

    /**
     * Get all metrics.
     *
     * @return Measurable[]
     */
    public function all(): array;

    /**
     * Flush all metrics.
     */
    public function flush(): void;
}
