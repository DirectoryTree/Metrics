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
     * The metric model to use.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    public static string $model = Metric::class;

    /**
     * Constructor.
     */
    public function __construct(
        protected MetricRepository $repository
    ) {}

    /**
     * Set the metric model to use.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public static function useModel(string $model): void
    {
        static::$model = $model;
    }

    /**
     * {@inheritDoc}
     */
    public function record(Measurable $metric): void
    {
        if ($this->capturing) {
            $this->repository->add($metric);
        } elseif ($queue = config('metrics.queue')) {
            RecordMetric::dispatch($metric)
                ->onQueue($queue['name'] ?? null)
                ->onConnection($queue['connection'] ?? null);
        } else {
            (new RecordMetric($metric))->handle();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function commit(): int
    {
        $metrics = $this->repository->all();

        if (empty($metrics)) {
            return 0;
        }

        if ($queue = config('metrics.queue')) {
            CommitMetrics::dispatch($metrics)
                ->onQueue($queue['name'] ?? null)
                ->onConnection($queue['connection'] ?? null);
        } else {
            (new CommitMetrics($metrics))->handle();
        }

        $this->repository->flush();

        return count($metrics);
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
