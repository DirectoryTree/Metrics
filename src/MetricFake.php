<?php

namespace DirectoryTree\Metrics;

use Closure;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert as PHPUnit;

class MetricFake implements MetricManager
{
    /**
     * Whether metrics are being captured.
     */
    protected bool $capturing = false;

    /**
     * The recorded metrics.
     *
     * @var Measurable[]
     */
    protected array $recorded = [];

    /**
     * {@inheritDoc}
     */
    public function record(Measurable $metric): void
    {
        $this->recorded[] = $metric;
    }

    /**
     * {@inheritDoc}
     */
    public function commit(): void
    {
        $this->recorded = [];
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

    /**
     * Assert that a metric was recorded.
     */
    public function assertRecorded(Closure|string $callback): void
    {
        PHPUnit::assertTrue(
            $this->recorded($callback)->isNotEmpty(),
            'The expected metric was not recorded.'
        );
    }

    /**
     * Assert that a metric was recorded.
     */
    public function assertRecordedTimes(Closure|string $callback, int $times = 1): void
    {
        PHPUnit::assertCount(
            $times,
            $this->recorded($callback),
            "The expected metric was recorded {$times} times, but {$times} was expected."
        );
    }

    /**
     * Assert that a metric was not recorded.
     */
    public function assertNotRecorded(Closure|string $callback): void
    {
        PHPUnit::assertTrue(
            $this->recorded($callback)->isEmpty(),
            'The unexpected metric was recorded.'
        );
    }

    /**
     * Assert that no metrics were recorded.
     */
    public function assertNothingRecorded(): void
    {
        PHPUnit::assertEmpty(
            $this->recorded,
            'Metrics were recorded unexpectedly.'
        );
    }

    /**
     * Get all recorded metrics.
     *
     * @return Collection<int, Measurable>
     */
    public function recorded(Closure|string|null $callback = null): Collection
    {
        $callback = $callback ?: fn () => true;

        return Collection::make($this->recorded)->when($callback)->filter(
            fn (Measurable $metric) => is_string($callback)
                ? $metric->name() === $callback
                : $callback($metric)
        );
    }
}
