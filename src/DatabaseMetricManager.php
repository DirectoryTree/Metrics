<?php

namespace DirectoryTree\Metrics;

use DirectoryTree\Metrics\Jobs\CommitMetrics;
use DirectoryTree\Metrics\Jobs\RecordMetric;

class DatabaseMetricManager implements MetricManager
{
    /**
     * Whether metrics are being captured.
     */
    protected bool $capturing = false;

    /**
     * Constructor.
     */
    public function __construct(
        protected MetricRepository $repository
    ) {}

    /**
     * {@inheritDoc}
     */
    public function record(Measurable $metric): void
    {
        if ($this->capturing) {
            $this->repository->add($metric);
        } elseif (config('metrics.queue')) {
            RecordMetric::dispatch($metric);
        } else {
            (new RecordMetric($metric))->handle();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function commit(): void
    {
        $metrics = $this->repository->all();

        if (empty($metrics)) {
            return;
        }

        if ($queue = config('metrics.queue')) {
            CommitMetrics::dispatch($metrics)
                ->onQueue($queue['queue'] ?? null)
                ->onConnection($queue['connection'] ?? null);
        } else {
            (new CommitMetrics($metrics))->handle();
        }

        $this->repository->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function capture(): void
    {
        $this->capturing = true;
    }

    /**
     * {@inheritDoc}
     */
    public function isCapturing(): bool
    {
        return $this->capturing;
    }

    /**
     * {@inheritDoc}
     */
    public function stopCapturing(): void
    {
        $this->capturing = false;
    }
}
