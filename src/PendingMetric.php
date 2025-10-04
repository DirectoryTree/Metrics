<?php

namespace DirectoryTree\Metrics;

use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class PendingMetric
{
    /**
     * The name of the metric.
     */
    protected BackedEnum|string $name;

    /**
     * The category of the metric.
     */
    protected BackedEnum|string|null $category = null;

    /**
     * The date of the metric.
     */
    protected ?CarbonInterface $date = null;

    /**
     * The measurable model of the metric.
     */
    protected ?Model $measurable = null;

    /**
     * Constructor.
     */
    public function __construct(BackedEnum|string $name)
    {
        $this->name = $name;
    }

    /**
     * Create a new pending metric.
     */
    public static function make(BackedEnum|string $name): self
    {
        return new self($name);
    }

    /**
     * Set the category of the metric.
     */
    public function category(BackedEnum|string|null $category): self
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Set the date of the metric.
     */
    public function date(CarbonInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Set the measurable model of the metric.
     */
    public function measurable(Model $measurable): self
    {
        $this->measurable = $measurable;

        return $this;
    }

    /**
     * Record the metric.
     */
    public function record(int $value = 1): void
    {
        app(MetricManager::class)->record(
            new MetricData(
                $this->name,
                $this->category,
                $value,
                $this->date,
                $this->measurable
            )
        );
    }
}
