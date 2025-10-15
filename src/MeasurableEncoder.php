<?php

namespace DirectoryTree\Metrics;

interface MeasurableEncoder
{
    /**
     * Encode a metric into a string.
     */
    public function encode(Measurable $metric): string;

    /**
     * Decode a metric string into a metric data.
     */
    public function decode(string $key, int $value): Measurable;
}
