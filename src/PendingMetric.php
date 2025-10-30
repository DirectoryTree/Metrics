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
     * The model to use for the metric.
     */
    protected ?string $model = null;

    /**
     * Whether to track hourly metrics.
     */
    protected bool $trackHourly = false;

    /**
     * Additional attributes to store with the metric.
     *
     * @var array<string, mixed>
     */
    protected array $additional = [];

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
    public static function make(BackedEnum|string $name): static
    {
        return new static($name);
    }

    /**
     * Set the category of the metric.
     */
    public function category(BackedEnum|string|null $category): static
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Set the date of the metric.
     */
    public function date(CarbonInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Set the measurable model of the metric.
     */
    public function measurable(Model $measurable): static
    {
        $this->measurable = $measurable;

        return $this;
    }

    /**
     * Set the metric model to use.
     */
    public function model(string $model): static
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Enable hourly tracking for the metric.
     */
    public function hourly(): static
    {
        $this->trackHourly = true;

        return $this;
    }

    /**
     * Set additional attributes to store with the metric.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function with(array $attributes): static
    {
        $this->additional = $attributes;

        return $this;
    }

    /**
     * Record the metric.
     */
    public function record(int $value = 1): void
    {
        app(MetricManager::class)->record(
            $this->toMetricData($value)
        );
    }

    /**
     * Convert the pending metric to a metric data object.
     */
    public function toMetricData(int $value): Measurable
    {
        return new MetricData(
            $this->name,
            $this->category,
            $value,
            $this->date,
            $this->measurable,
            $this->additional,
            $this->trackHourly,
            $this->model,
        );
    }
}
