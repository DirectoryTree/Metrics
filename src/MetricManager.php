<?php

namespace DirectoryTree\Metrics;

interface MetricManager
{
    /**
     * Record a metric.
     */
    public function record(Measurable $metric): void;

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
