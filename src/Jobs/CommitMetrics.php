<?php

namespace DirectoryTree\Metrics\Jobs;

use DirectoryTree\Metrics\Measurable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Collection;

class CommitMetrics implements ShouldQueue
{
    use Dispatchable, Queueable;

    /**
     * Constructor.
     */
    public function __construct(
        public array $metrics,
        public bool $shouldQueue,
    ) {}

    /**
     * Commit all metrics to the database.
     */
    public function handle(): void
    {
        Collection::make($this->metrics)
            ->groupBy(function (Measurable $data) {
                return implode(array_filter([
                    $data->name(),
                    $data->category(),
                    $data->year(),
                    $data->month(),
                    $data->day(),
                    $data->measurable()?->getKey() ?? null,
                    $data->measurable()?->getMorphClass() ?? null,
                ]));
            })
            ->each(function (Collection $metrics) {
                if ($this->shouldQueue) {
                    RecordMetric::dispatch($metrics);
                } else {
                    (new RecordMetric($metrics))->handle();
                }
            });
    }
}
