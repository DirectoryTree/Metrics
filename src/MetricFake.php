<?php

namespace DirectoryTree\Metrics;

class MetricFake implements MetricManager
{
    protected bool $capturing = false;

    protected array $recorded = [];

    public function record(Measurable $metric): ?Metric
    {
        $this->recorded[] = $metric;

        return null;
    }

    public function commit(): void
    {
        $this->recorded = [];
    }

    public function capture(): void
    {
        $this->capturing = true;
    }

    public function isCapturing(): bool
    {
        return $this->capturing;
    }

    public function stopCapturing(): void
    {
        $this->capturing = false;
    }
}
