<?php

namespace DirectoryTree\Metrics;

interface MetricsManager
{
    /**
     * Record a metric.
     */
    public function record(Measurable $metric): ?Metric;

    /**
     * Commit all recorded metrics.
     */
    public function commit(): void;

    /**
     * Start capturing metrics.
     */
    public function capture(): void;

    /**
     * Determine if metrics are being captured.
     */
    public function isCapturing(): bool;

    /**
     * Stop capturing metrics.
     */
    public function stopCapturing(): void;
}
