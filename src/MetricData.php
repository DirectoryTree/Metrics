<?php

namespace DirectoryTree\Metrics;

use BackedEnum;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class MetricData implements Measurable
{
    /**
     * Constructor.
     */
    public function __construct(
        protected BackedEnum|string $name,
        protected BackedEnum|string|null $category = null,
        protected int $value = 1,
        protected ?CarbonInterface $date = null,
        protected ?Model $measurable = null,
    ) {
        $this->date ??= new CarbonImmutable;
    }

    /**
     * {@inheritDoc}
     */
    public function name(): BackedEnum|string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function category(): BackedEnum|string|null
    {
        return $this->category;
    }

    /**
     * {@inheritDoc}
     */
    public function value(): int
    {
        return $this->value;
    }

    /**
     * {@inheritDoc}
     */
    public function year(): int
    {
        return $this->date->year;
    }

    /**
     * {@inheritDoc}
     */
    public function month(): int
    {
        return $this->date->month;
    }

    /**
     * {@inheritDoc}
     */
    public function day(): int
    {
        return $this->date->day;
    }

    /**
     * {@inheritDoc}
     */
    public function measurable(): ?Model
    {
        return $this->measurable;
    }
}
