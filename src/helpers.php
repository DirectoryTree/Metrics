<?php

namespace DirectoryTree\Metrics;

use BackedEnum;

if (! function_exists('metric')) {
    /**
     * Create a new pending metric.
     */
    function metric(BackedEnum|string $name): PendingMetric
    {
        return new PendingMetric($name);
    }
}
