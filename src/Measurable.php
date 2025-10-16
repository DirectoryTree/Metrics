<?php

namespace DirectoryTree\Metrics;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;

interface Measurable
{
    /**
     * Get the name of the metric.
     */
    public function name(): BackedEnum|string;

    /**
     * Get the category of the metric.
     */
    public function category(): BackedEnum|string|null;

    /**
     * Get the value of the metric.
     */
    public function value(): int;

    /**
     * Get the year of the metric.
     */
    public function year(): int;

    /**
     * Get the month of the metric.
     */
    public function month(): int;

    /**
     * Get the day of the metric.
     */
    public function day(): int;

    /**
     * Get the measurable model of the metric.
     */
    public function measurable(): ?Model;

    /**
     * Get the additional attributes of the metric.
     */
    public function additional(): array;
}
