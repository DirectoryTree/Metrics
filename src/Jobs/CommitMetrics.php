<?php

namespace DirectoryTree\Metrics\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class CommitMetrics implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

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
        foreach ($this->metrics as $metric) {
            if ($this->shouldQueue) {
                RecordMetric::dispatch($metric);
            } else {
                (new RecordMetric($metric))->handle();
            }
        }
    }
}
