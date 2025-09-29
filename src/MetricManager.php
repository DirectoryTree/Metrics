<?php

namespace DirectoryTree\Metrics;

class MetricManager
{
    protected bool $capturing = false;

    /**
     * Constructor.
     */
    public function __construct(
        protected MetricRepository $repository
    ) {}

    /**
     * Record a metric.
     */
    public function record(Measurable $metric): ?self
    {
        if ($this->capturing) {
            $this->repository->add($metric);
        } elseif (config('metrics.queue')) {
            RecordMetric::dispatch($metric);
        } else {
            return RecordMetric::dispatchSync($metric);
        }

        return null;
    }

    /**
     * Commit all metrics to the database.
     */
    public function commit(): void
    {
        $metrics = $this->repository->all();

        if (empty($metrics)) {
            return;
        }

        if ($queue = config('metrics.queue')) {
            CommitMetrics::dispatch($metrics, $queue);
        } else {
            CommitMetrics::dispatchSync($metrics, $queue);
        }

        $this->repository->flush();
    }

    /**
     * Start capturing metrics.
     */
    public function capture(): void
    {
        $this->capturing = true;
    }

    /**
     * Determine if metrics are being captured.
     */
    public function isCapturing(): bool
    {
        return $this->capturing;
    }

    /**
     * Stop capturing metrics.
     */
    public function stopCapturing(): void
    {
        $this->capturing = false;
    }
}
